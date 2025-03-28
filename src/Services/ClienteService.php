<?php

namespace App\Services;

use Core\Contracts\ServiceInterface;
use Core\Exceptions\{ValidationException, DatabaseException};
use Core\Http\{Request, Response};
use Core\Validation\Validator;
use App\Repositories\ClienteRepository;
use App\Contracts\ClienteServiceInterface;

class ClienteService implements ServiceInterface, ClienteServiceInterface
{
    private $repository;
    private $validator;
    private $camposDefault = ['id_cliente', 'cedula', 'nombre', 'apellidos', 'direccion', 'barrio', 'telefono', 'email', 'estado', 'created_at', 'updated_at'];
    private $camposSiempreExcluidos = [];

    public function __construct(ClienteRepository $repository, Validator $validator)
    {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function readAll(Request $request, Response $response): Response
    {
        try {
            $clientes = $this->repository->all(); // Ajustado de getAll() a all()

            $clientesFormateados = array_map(
                fn($cliente) => $this->formatUser(
                    $cliente,
                    $this->camposDefault,
                    $this->camposSiempreExcluidos,
                    false
                ),
                $clientes
            );

            return $response->json(['data' => $clientesFormateados], 200);
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode());
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $cliente = $request->input();

            // Convertir datos de tipo string a minúsculas
            foreach ($cliente as $key => $value) {
                if (is_string($value)) {
                    $cliente[$key] = strtolower($value);
                }
            }

            $this->validator->validate($cliente);

            if (isset($cliente['cedula']) && $this->repository->findByCedula($cliente['cedula'])) {
                throw new ValidationException(['cedula' => ['La cédula ya está registrada']]);
            }

            $id = (int) $this->repository->create($cliente);
            return $response->json(['message' => 'Cliente creado exitosamente', 'id' => $id], 201);
        } catch (ValidationException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getErrors()], 400);
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode());
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function search(Request $request, Response $response): Response
    {
        try {
            $params = $request->query();
            $allowedParams = ['nombre', 'apellidos', 'cedula', 'telefono'];
            $data = array_intersect_key($params, array_flip($allowedParams));

            if (empty($data)) {
                return $response->json(['error' => 'Debe proporcionar al menos un criterio de búsqueda'], 400);
            }

            $this->validator->validate($data);
            $clientes = $this->repository->search($data);

            if (empty($clientes)) {
                return $response->json(['message' => 'No se encontraron clientes con esos criterios'], 404);
            }

            $clientesFormateados = array_map(
                fn($cliente) => $this->formatUser(
                    $cliente,
                    $this->camposDefault,
                    $this->camposSiempreExcluidos,
                    false
                ),
                $clientes
            );

            return $response->json(['data' => $clientesFormateados], 200);
        } catch (ValidationException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getErrors()], 400);
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode());
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function read(Request $request, Response $response, $id = null): Response
    {
        try {
            $data = ['id' => $id];
            $this->validator->validate($data);

            $cliente = $this->repository->find($id);

            if (!$cliente) {
                return $response->json(['error' => 'Cliente no encontrado'], 404);
            }

            $clienteFormateado = $this->formatUser(
                $cliente,
                $this->camposDefault,
                $this->camposSiempreExcluidos,
                false
            );

            return $response->json(['data' => $clienteFormateado], 200);
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
            $clienteExistente = $this->repository->find($id);
            if (!$clienteExistente) {
                return $response->json(['error' => 'Cliente no encontrado'], 404);
            }

            $data = $request->input();
            if (empty($data)) {
                return $response->json(['error' => 'No se proporcionaron datos para actualizar'], 400);
            }

            // Convertir datos de tipo string a minúsculas
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = strtolower($value);
                }
            }

            $this->validator->validate($data);

            if (isset($data['cedula']) && $data['cedula'] !== $clienteExistente['cedula']) {
                if ($this->repository->findByCedula($data['cedula'])) {
                    throw new ValidationException(['cedula' => ['La cédula ya está registrada por otro cliente']]);
                }
            }

            $clienteFormateado = $this->formatUser(
                $data,
                $this->camposDefault,
                $this->camposSiempreExcluidos,
                false
            );

            // Determinar si es PUT (completa) o PATCH (parcial)
            $dataToUpdate = $request->method() === 'PUT'
                ? array_merge($clienteFormateado, $data)
                : array_merge($clienteExistente, $data);

            $this->repository->update($id, $dataToUpdate);
            $clienteActualizado = $this->formatUser($dataToUpdate, $this->camposDefault, $this->camposSiempreExcluidos, false);

            return $response->json([
                'message' => 'Cliente actualizado exitosamente',
                'data' => $clienteActualizado
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
            $data = ['id' => $id];
            $this->validator->validate($data);

            $cliente = $this->repository->find($id);
            if (!$cliente) {
                return $response->json(['error' => 'Cliente no encontrado'], 404);
            }

            $dataToUpdate = ['estado' => 'inactivo'];
            $this->repository->update($id, $dataToUpdate);

            return $response->json(['message' => 'Cliente marcado como inactivo exitosamente'], 200);
        } catch (ValidationException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getErrors()], 400);
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode());
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    private function formatUser(
        array $cliente,
        array $camposIncluir = [],
        array $camposExcluir = [],
        bool $forzarIncluirExcluidos = false
    ): array {
        $camposDefault = [
            'id_cliente' => fn($id) => (int) $id,
            'cedula' => fn($cedula) => (int) $cedula,
            'nombre' => fn($nombre) => ucfirst($nombre),
            'apellidos' => fn($apellidos) => ucfirst($apellidos),
            'direccion' => fn($direccion) => $direccion,
            'barrio' => fn($barrio) => $barrio,
            'telefono' => fn($telefono) => (int) $telefono,
            'email' => fn($email) => $email,
            'estado' => fn($estado) => $estado,
            'created_at' => fn($created_at) => $created_at ? (new \DateTime($created_at))->format('d-m-Y') : null,
            'updated_at' => fn($updated_at) => $updated_at ? (new \DateTime($updated_at))->format('d-m-Y') : null
        ];

        $camposDisponibles = array_keys($camposDefault);
        $campos = empty($camposIncluir) ? $camposDisponibles : $camposIncluir;

        $camposInvalidos = array_diff($campos, $camposDisponibles);
        if (!empty($camposInvalidos)) {
            throw new ValidationException(['campos' => ['Los siguientes campos no son válidos: ' . implode(', ', $camposInvalidos)]]);
        }

        $resultado = [];
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $cliente)) {
                if ($forzarIncluirExcluidos || !in_array($campo, $camposExcluir)) {
                    $resultado[$campo] = isset($camposDefault[$campo])
                        ? $camposDefault[$campo]($cliente[$campo])
                        : $cliente[$campo];
                }
            }
        }

        return $resultado;
    }
}