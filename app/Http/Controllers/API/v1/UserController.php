<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Mail\ConfirmEmail;
use App\Services\Facebook\UserInformation;
use App\Services\Valutowallet\UserCheck;
use App\Services\Valutowallet\UserCreate;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Redirect, Config;

class UserController extends Controller
{
    /**
     * URL to valutowallet API endpoint that will create 
     * the new wallet account.
     * 
     * @var string
     */
    protected $createWalletEndpoint = '';

    /**
     * User information service.
     *
     * @var App\Services\Facebook\UserInformation
     */
    protected $userInformation;

    /**
     * User check service for Valutowallet.
     *
     * @var App\Services\Valutowallet\UserCheck
     */
    protected $walletUserCheck;

    /**
     * User create service for Valutowallet.
     *
     * @var App\Services\Valutowallet\UserCreate
     */
    protected $walletUserCreate;

    /**
     * Instantiate controller with dependencies.
     *
     * @param UserInformation $userInformation
     * @param UserCheck       $userCheck
     * @param UserCreate      $userCreate
     */
    public function __construct(UserInformation $userInformation, UserCheck $walletUserCheck, UserCreate $walletUserCreate)
    {
        $this->userInformation  = $userInformation;
        $this->walletUserCheck  = $walletUserCheck;
        $this->walletUserCreate = $walletUserCreate;
    }

    /**
     * Create new user and send confirmation email.
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'facebook_access_token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $email = mb_strtolower($request->email);

        $facebookUser = $this->userInformation->get($request->facebook_access_token, [
            'fields' => 'id,first_name,middle_name,last_name,email',
        ]);

        if (mb_strtolower($facebookUser->getEmail()) !== $email) {
            return response()->json(['error' => 'Email obtained from Facebook does not match supplied email.'], 400);
        }

        // Check in local bounty signup database.
        $existsLocally = User::where('email', 'LIKE', $email)->exists();
        if ($existsLocally) {
            return response()->json(['error' => 'Email already signed up for bounty.'], 400);
        }

        // Check user existence in wallet.
        if ($this->walletUserCheck->email($email)) {
            return response()->json(['error' => 'Email already signed up on Valutowallet/VLU Market.'], 400);
        }

        // Check user existence in wallet.
        if ($this->walletUserCheck->username($email)) {
            return response()->json(['error' => 'Email already signed up on Valutowallet/VLU Market.'], 400);
        }

        $lastName = implode(' ', [$facebookUser->getMiddleName(), $facebookUser->getLastName()]);

        $input = $request->all();
        $input['email']             = $email;
        $input['password']          = bcrypt(str_random(40));
        $input['ip_address']        = $request->ip();
        $input['first_name']        = $facebookUser->getFirstName();
        $input['last_name']         = $lastName;
        $input['facebook_id']       = $facebookUser->getId();
        $input['confirmation_code'] = str_random(20);

        $user = User::create($input);

        try {
            $created = $this->walletUserCreate->store([
                'client_ip'   => $input['ip_address'],
                'bounty'      => TRUE,
                'particulars' => [
                    'email'      => $input['email'],
                    'username'   => $input['email'],
                    'first_name' => $input['first_name'],
                    'last_name'  => $input['last_name'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'We could not create your user on Valutowallet/VLU Market. Please contact us at info@valuto.io'], 400);
        }

        // For email confirmation without facebook validation:
        //Mail::to($user)->send(new ConfirmEmail($input['name'], $input['confirmation_code'], $user->id));
        //return response()->json(['success' => 'Confirmation email sent!']);

        return response()->json([
            'success'  => 'user_created',
            'redirect' => Config::get('valutowallet.baseurl') . 'user/activate?user_id=' . $created->user_id . '&token=' . urlencode($created->setPasswordToken),
        ]);
    }

    /**
     * Verify user creation.
     *
     * @param  string $confirmationCode
     * @param  int    $userId
     * @return \Illuminate\Http\Response
     */
    public function verify($confirmationCode, $userId, Request $request)
    {
        if (!$confirmationCode) {
            throw new Exception('No confirmation code supplied.');
        }

        $user = User::whereId($userId)
                    ->whereConfirmationCode($confirmationCode)
                    ->whereNotNull('confirmation_code')
                    ->whereNull('confirmed')
                    ->first();

        if (!$user) {
            throw new Exception('Wrong information code.');
        }

        $user->confirmed = now();
        $user->confirmation_code = null;
        $user->save();

        return redirect('');
    }
}
