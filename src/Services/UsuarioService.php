<?php

namespace App\Services;

use Core\Http\{Request, Response};
use Core\Validation\Validator;
use Core\Contracts\ServiceInterface;
use Core\Exceptions\{ValidationException, DatabaseException};
use App\Repositories\UsuarioRepository;
use App\Contracts\UsuarioServiceInterface;

class UsuarioService implements ServiceInterface, UsuarioServiceInterface {
    private $repository;
    private $validator;
    private $camposDefault = ['id_usuario', 'cedula', 'nombre', 'apellidos', 'email', 'role', 'status', 'registro', 'created_at', 'updated_at', 'password'];
    private $camposSiempreExcluidos = ['password'];

    public function __construct(UsuarioRepository $repository, Validator $validator) {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function readAll(Request $request, Response $response): Response
    {
        try {
            $usuarios = $this->repository->all(); // Ajustado de getAll() a all()

            $usuariosFormateados = array_map(
                fn($usuario) => $this->formatUser(
                    $usuario,
                    $this->camposDefault,
                    $this->camposSiempreExcluidos,
                    false
                ),
                $usuarios
            );

            return $response->json(['data' => $usuariosFormateados], 200);
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode());
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function read(Request $request, Response $response, $id = null): Response
    {
        try {
            // Validar que el ID sea numérico
            $data = ['id' => $id];
            $this->validator->validate($data);

            // Buscar el usuario en el repositorio
            $usuario = $this->repository->getById($id);

            if (!$usuario) {
                return $response->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Formatear el resultado
            $usuarioFormateado = $this->formatUser(
                $usuario,
                $this->camposDefault,
                $this->camposSiempreExcluidos,
                false
            );

            return $response->json(['data' => $usuarioFormateado], 200);
        } catch (ValidationException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getErrors()], 400);
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode());
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $usuario = $request->input();
            $this->validator->validate($usuario);

            // Validación adicional específica del negocio
            if (isset($usuario['email']) && $this->repository->findByEmail($usuario['email'])) {
                throw new ValidationException(['email' => ['El email ya está en uso']]);
            }

            $id = (int) $this->repository->create($usuario);
            return $response->json(['message' => 'Usuario creado exitosamente', 'id' => $id], 201);
        } catch (ValidationException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getErrors()], 400);
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode());
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Response $response, $id): Response
    {
        try {
            $usuarioExistente = $this->repository->find($id);
            if (!$usuarioExistente) {
                return $response->json(['error' => 'Usuario no encontrado'], 404);
            }

            $data = $request->input();
            if (empty($data)) {
                return $response->json(['error' => 'No se proporcionaron datos para actualizar'], 400);
            }

            $this->validator->validate($data);

            if (isset($data['email']) && $data['email'] !== $usuarioExistente['email']) {
                if ($this->repository->findByEmail($data['email'])) {
                    throw new ValidationException(['email' => ['El email ya está en uso']]);
                }
            }

            // Si password no viene en los datos, lo eliminamos del usuario existente para que no se actualice
            if (!isset($data['password']) && isset($usuarioExistente['password'])) {
                unset($usuarioExistente['password']);
            }

            // Formatear el resultado
            $usuarioFormateado = $this->formatUser(
                $data,
                $this->camposDefault,
                $this->camposSiempreExcluidos,
                false
            );

            // Determinar si es PUT (completa) o PATCH (parcial)
            $dataToUpdate = $request->method() === 'PUT' 
                ? array_merge($usuarioFormateado, $data) // Reemplaza todo con valores por defecto si falta
                : array_merge($usuarioExistente, $data); // Solo actualiza lo enviado

            $this->repository->update($id, $dataToUpdate);
            $usuarioActualizado = $this->formatUser($dataToUpdate, $this->camposDefault, $this->camposSiempreExcluidos, false);

            return $response->json([
                'message' => 'Usuario actualizado exitosamente',
                'data' => $usuarioActualizado
            ], 200);
        } catch (ValidationException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getErrors()], 400);
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode());
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request, Response $response, $id): Response
    {
        try {
            // Verificar si el usuario existe
            $usuario = $this->repository->find($id);
            if (!$usuario) {
                return $response->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Actualizar el estado a 'inactivo'
            $dataToUpdate = ['status' => 'inactivo'];
            $this->repository->update($id, $dataToUpdate);

            return $response->json(['message' => 'Usuario marcado como inactivo exitosamente'], 200);
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode());
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    private function formatUser(
        array $usuario,
        array $camposIncluir = [],
        array $camposExcluir = ['password'],
        bool $forzarIncluirExcluidos = false
    ): array {
        $camposDefault = [
            'id_usuario' => fn($id) => (int) $id,
            'cedula' => fn($cedula) => (int) $cedula,
            'nombre' => fn($nombre) => $nombre,
            'apellidos' => fn($apellidos) => $apellidos,
            'email' => fn($email) => $email,
            'role' => fn($role) => $role,
            'status' => fn($status) => $status,
            'registro' => fn($registro) => $registro,
            'created_at' => fn($created_at) => $created_at ? (new \DateTime($created_at))->format('d-m-Y') : null,
            'updated_at' => fn($updated_at) => $updated_at ? (new \DateTime($updated_at))->format('d-m-Y') : null,
            'password' => fn($password) => $password // Campo sensible opcional
        ];

        $camposDisponibles = array_keys($camposDefault);
        $campos = empty($camposIncluir) ? $camposDisponibles : $camposIncluir;

        // Validar campos
        $camposInvalidos = array_diff($campos, $camposDisponibles);
        if (!empty($camposInvalidos)) {
            throw new ValidationException(['campos' => ["Los siguientes campos no son válidos: " . implode(', ', $camposInvalidos)]]);
        }

        $resultado = [];
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $usuario)) {
                if ($forzarIncluirExcluidos || !in_array($campo, $camposExcluir)) {
                    $resultado[$campo] = isset($camposDefault[$campo])
                        ? $camposDefault[$campo]($usuario[$campo])
                        : $usuario[$campo];
                }
            }
        }

        return $resultado;
    }
}