<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Env;
use App\Core\Validator;
use App\Core\Session;
use App\Models\User;
use App\Models\Channel;
use App\Services\AuthService;

class AuthController extends BaseController
{
    public function showLogin(Request $request): void
    {
        if (is_logged_in()) {
            $this->redirect(url(''));
        }
        echo $this->render('login', ['title' => 'Вход', 'page_title' => 'Вход']);
    }

    public function login(Request $request): void
    {
        if (is_logged_in()) {
            $this->redirect(url(''));
        }
        $login = trim((string)$request->post('login', ''));
        $password = (string)$request->post('password', '');

        $errors = [];
        if ($login === '' || $password === '') {
            $errors['login'] = 'Введите email/логин и пароль.';
        }
        if (!empty($errors)) {
            set_flash('error', 'Заполните все поля.');
            Session::set('old_input', ['login' => $login]);
            $this->redirect(url('login'));
        }

        // Simple brute-force dampening.
        $user = AuthService::attempt($login, $password);
        if (!$user) {
            sleep(1);
            set_flash('error', 'Неверный логин или пароль.');
            Session::set('old_input', ['login' => $login]);
            $this->redirect(url('login'));
        }

        AuthService::login($user);
        set_flash('success', 'Добро пожаловать в GlassTube!');
        $this->redirect(url(''));
    }

    public function showRegister(Request $request): void
    {
        if (!Env::bool('REGISTRATION_OPEN', true)) {
            set_flash('error', 'Регистрация временно закрыта.');
            $this->redirect(url(''));
        }
        if (is_logged_in()) {
            $this->redirect(url(''));
        }
        echo $this->render('register', ['title' => 'Регистрация', 'page_title' => 'Регистрация']);
    }

    public function register(Request $request): void
    {
        if (!Env::bool('REGISTRATION_OPEN', true)) {
            set_flash('error', 'Регистрация временно закрыта.');
            $this->redirect(url(''));
        }
        $data = [
            'username'  => trim((string)$request->post('username', '')),
            'email'     => trim((string)$request->post('email', '')),
            'password'  => (string)$request->post('password', ''),
            'password2' => (string)$request->post('password2', ''),
            'accept'    => (string)$request->post('accept_terms', ''),
        ];

        $validator = new Validator();
        $errors = $validator->validate($data, [
            'username' => 'required|min:3|max:40',
            'email'    => 'required|email|max:191',
            'password' => 'required|min:8',
        ]);

        if ($data['password'] !== $data['password2']) {
            $errors['password2'] = 'Пароли не совпадают.';
        }
        if ($data['accept'] !== '1') {
            $errors['accept'] = 'Необходимо принять правила сообщества.';
        }
        if (User::findByEmail($data['email'])) {
            $errors['email'] = 'Этот email уже используется.';
        }
        if (User::findByUsername($data['username'])) {
            $errors['username'] = 'Этот логин уже занят.';
        }

        if (!empty($errors)) {
            set_flash('error', 'Проверьте правильность заполнения формы.');
            Session::set('old_input', ['username' => $data['username'], 'email' => $data['email']]);
            $this->redirect(url('register'));
        }

        $userId = AuthService::register([
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);
        $user = User::findById($userId);
        AuthService::login($user);
        set_flash('success', 'Аккаунт создан. Добро пожаловать!');
        $this->redirect(url(''));
    }

    public function logout(Request $request): void
    {
        AuthService::logout();
        $this->redirect(url('login'));
    }

    public function settings(Request $request): void
    {
        $user = $this->user();
        $channel = Channel::findByUserId((int)$user['id']);
        echo $this->render('settings', [
            'title'    => 'Настройки профиля',
            'page_title' => 'Настройки',
            'channel'  => $channel,
        ]);
    }

    public function updateSettings(Request $request): void
    {
        $userId = (int)$this->user()['id'];
        $bio = trim((string)$request->post('bio', ''));
        $channelName = trim((string)$request->post('channel_name', ''));
        $channelDesc = trim((string)$request->post('channel_description', ''));
        $current = (string)$request->post('current_password', '');
        $newPass = (string)$request->post('new_password', '');

        $errors = [];
        if ($channelName === '') {
            $errors['channel_name'] = 'Укажите название канала.';
        }

        // Optional password change.
        if ($newPass !== '') {
            if (mb_strlen($newPass) < 8) {
                $errors['new_password'] = 'Минимум 8 символов.';
            } else {
                $user = User::findById($userId);
                if ($current === '' || !password_verify($current, $user['password_hash'])) {
                    $errors['current_password'] = 'Неверный текущий пароль.';
                }
            }
        }

        if (!empty($errors)) {
            set_flash('error', 'Не удалось сохранить изменения.');
            Session::set('old_input', ['bio' => $bio, 'channel_name' => $channelName, 'channel_description' => $channelDesc]);
            $this->redirect(url('settings'));
        }

        User::update($userId, ['bio' => $bio === '' ? null : $bio]);
        $channel = Channel::findByUserId($userId);
        if ($channel) {
            Channel::update($channel['id'], [
                'name'        => $channelName,
                'description' => $channelDesc === '' ? null : $channelDesc,
            ]);
        }
        if ($newPass !== '') {
            User::update($userId, ['password_hash' => password_hash($newPass, PASSWORD_DEFAULT)]);
        }

        AuthService::refresh($userId);
        set_flash('success', 'Профиль обновлён.');
        $this->redirect(url('settings'));
    }
}
