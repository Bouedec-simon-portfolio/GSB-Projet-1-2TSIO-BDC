<?php
namespace App\Controllers;
use App\Models\VisiteurModel;
class Auth extends BaseController
{
    protected $helpers = ['url', 'form'];
    public function index()
    {
        if (session()->has('user')) return redirect()->to(site_url('fiches'));
        return view('connexion');
    }
    public function login()
    {
        if (!service('throttler')->check('login-'.hash('sha256', $this->request->getIPAddress()), 10, 60)) {
            return redirect()->to(site_url('connexion'))->with('error', 'Trop de tentatives. Réessayez dans une minute.');
        }
        $login = $this->request->getPost('login');
        $password = $this->request->getPost('password');
        $user = is_string($login) ? (new VisiteurModel())->where('login', trim($login))->first() : null;
        if (!$user || !is_string($password) || !password_verify($password, $user['mdp'])) {
            return redirect()->to(site_url('connexion'))->with('error', 'Identifiant ou mot de passe incorrect.');
        }
        if (password_needs_rehash($user['mdp'], PASSWORD_DEFAULT)) {
            (new VisiteurModel())->update($user['id'], ['mdp'=>password_hash($password, PASSWORD_DEFAULT)]);
        }
        session()->regenerate(true);
        session()->set('user', array_intersect_key($user, array_flip(['id','nom','prenom','login'])));
        return redirect()->to(site_url('fiches'));
    }
    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('connexion'));
    }
}
