<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ConfirmEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * User ID.
     * 
     * @var int
     */
    protected $userId;

    /**
     * Name.
     * 
     * @var string
     */
    protected $name;

    /**
     * Confirmation code.
     * 
     * @var string
     */
    protected $confirmationCode;

    /**
     * Create a new message instance.
     *
     * @param  string $name
     * @param  string $confirmationCode
     * @param  int    $userId
     * @return void
     */
    public function __construct($name, $confirmationCode, $userId)
    {
        $this->name = $name;
        $this->confirmationCode = $confirmationCode;
        $this->userId = $userId;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('info@valuto.io')
                    ->subject('Confirm your email')
                    ->markdown('emails.bounty.confirm_email')
                    ->with([
                        'name' => $this->name,
                        'confirmationCode' => $this->confirmationCode,
                        'userId' => $this->userId,
                    ]);
    }
}
