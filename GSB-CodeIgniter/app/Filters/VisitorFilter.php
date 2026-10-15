<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class VisitorFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!empty(session('user')['admin'])) {
            return redirect()->to(site_url('administration'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
