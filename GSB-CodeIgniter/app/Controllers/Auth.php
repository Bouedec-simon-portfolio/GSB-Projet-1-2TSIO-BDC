<?php
namespace App\Controllers;
use App\Models\VisiteurModel;
class Auth extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function destination(): string
    {
        return !empty(session('user')['admin']) ? 'administration' : 'fiches';
    }

    public function index()
    {
        if (session()->has('user')) return redirect()->to(site_url($this->destination()));
        return view('connexion');
    }
    public function login()
    {
        $login = $this->request->getPost('login');
        $password = $this->request->getPost('password');
        $user = is_string($login) ? (new VisiteurModel())->where('login', trim($login))->first() : null;
        if (!$user || !is_string($password) || !password_verify($password, $user['mdp'])) {
            return redirect()->to(site_url('connexion'))->with('error', 'Identifiant ou mot de passe incorrect.');
        }
        $adminIds = array_map('trim', explode(',', (string) env('gsb.adminIds', 'a00')));
        $sessionUser = array_intersect_key($user, array_flip(['id', 'nom', 'prenom', 'login']));
        $sessionUser['admin'] = in_array($user['id'], $adminIds, true);

        session()->regenerate(true);
        session()->set('user', $sessionUser);
        return redirect()->to(site_url($this->destination()));
    }
    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('connexion'));
    }
}
