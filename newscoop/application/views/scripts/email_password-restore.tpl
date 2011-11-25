Hello,

to reset your password, follow link below:

http://{{ $publication }}{{ $view->url(['controller' => 'auth', 'action' => 'password-restore-finish', 'user' => $user->identifier, 'token' => $token], 'default') }}

Thanks!
{{ $view->placeholder('subject')->set('Restore password') }}
