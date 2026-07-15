<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\Mailer;
use Throwable;

final class PublicController extends Controller
{
    public function soon(): void
    {
        $this->view('public/soon', ['title' => 'RQCode Sistemas e Servicos'], 'public');
    }

    public function contact(): void
    {
        $name = trim((string) $this->input('name'));
        $email = trim((string) $this->input('email'));
        $phone = trim((string) $this->input('phone'));
        $message = trim((string) $this->input('message'));

        if ($name === '' || $email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['site_flash'] = [
                'type' => 'error',
                'message' => 'Preencha nome, e-mail valido e mensagem.',
            ];
            redirect('/#contato');
        }

        $body = implode("\n", [
            'Novo contato pelo site RQCode',
            '',
            "Nome: {$name}",
            "E-mail: {$email}",
            "Telefone/WhatsApp: {$phone}",
            '',
            'Mensagem:',
            $message,
        ]);

        try {
            (new Mailer())->send(
                env('MAIL_TO_CONTACT', env('MAIL_FROM', 'contato@rqcode.com.br')),
                'Contato pelo site RQCode',
                $body,
                $email,
                $name
            );
            $_SESSION['site_flash'] = [
                'type' => 'success',
                'message' => 'Mensagem enviada. Em breve retornaremos o contato.',
            ];
        } catch (Throwable) {
            $_SESSION['site_flash'] = [
                'type' => 'error',
                'message' => 'Nao foi possivel enviar agora. Chame pelo WhatsApp.',
            ];
        }

        redirect('/#contato');
    }
}
