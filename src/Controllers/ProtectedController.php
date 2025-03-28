<?php

namespace App\Controllers;

use Core\Http\{Request, Response};

class ProtectedController {
    public function index(Request $request, Response $response) {
        // $request->user contiene los datos del token (user_id, email)
        return $response->json([
            'message' => 'This is a protected route',
            'user' => $request->user
        ]);
    }
}