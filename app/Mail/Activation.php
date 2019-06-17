<?php namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Activation extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string the address to send the email */
    protected $toAddress;

    /** @var string the winnings they won */
    protected $activationCode;

    /**
     * Create a new message instance.
     *
     * @param string $toAddress the address to send the email
     * @param string $activationCode
     *
     * @return void
     */
    public function __construct(string $toAddress, string $activationCode)
    {
        $this->toAddress = $toAddress;
        $this->activationCode = $activationCode;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->to($this->toAddress)
            ->subject('Your auth activated')
            ->view('emails.activation')
            ->with(
                [
                    'activationCode' => $this->activationCode,
                ]
            );
    }
}
