<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Activation extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @var string the address to send the email */
    protected $toAddress;

    /** @var string activation code */
    protected $activationCode;

    /**
     * Create a new message instance.
     *
     * @param string $toAddress      the address to send the email
     * @param string $activationCode
     */
    public function __construct(string $toAddress, string $activationCode)
    {
        $this->toAddress      = $toAddress;
        $this->activationCode = $activationCode;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): Activation
    {
        return $this
            ->to($this->toAddress)
            ->subject('Your auth account is activated.')
            ->view('emails.activation')
            ->with(
                [
                    'activationCode' => $this->activationCode,
                ]
            );
    }
}
