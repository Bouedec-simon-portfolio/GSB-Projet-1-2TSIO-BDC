<?php
namespace App\Models;
use CodeIgniter\Model;
class VisiteurModel extends Model
{
    protected $table = 'Visiteur';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = ['id','nom','prenom','login','mdp'];
}
