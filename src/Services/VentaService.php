<?php

namespace App\Services;

use Core\Contracts\ServiceInterface;
use Core\Exceptions\{ValidationException, DatabaseException};
use Core\Http\{Request, Response};
use Core\Traits\DataTransformer;
use Core\Validation\Validator;
use App\Contracts\VentaServiceInterface;
use App\Repositories\VentaRepository;

class VentaService implements ServiceInterface, VentaServiceInterface
{
    use DataTransformer;

    private $repository;
    private $validator;
    private $camposDefault = ['id_venta', 'id_sucursal', 'descuento', 'total', 'status', 'nota', 'mediopago', 'metodo_pago', 'estado', 'id_cliente', 'id_usuario', 'cajero', 'vendedor', 'created_at', 'updated_at'];
    private $camposSiempreExcluidos = ['tipoventa', 'paga_con', 'devolucion', 'pagacon', 'cambio', 'abono', 'saldo', 'entregado', 'registro', 'mod'];

    public function __construct(VentaRepository $repository, Validator $validator)
    {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function readAll(Request $request, Response $response)
    {
        try {
            $ventas = $this->repository->getAll();
            $ventasFiltradas = $this->filtrarCamposExcluidos($ventas);
            return $response->json(['data' => $ventasFiltradas], 200);
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
            $data = ['id_venta' => $id];
            $this->validator->validate($data);

            // Buscar la venta en el repositorio
            $venta = $this->repository->getById($id);

            if (!$venta) {
                return $response->json(['error' => 'Venta no encontrada'], 404);
            }

            // Formatear el resultado
            $ventaFormateada = $this->filtrarCamposExcluidos($venta);

            return $response->json(['data' => $ventaFormateada], 200);
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode());
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response): Response {
        try {
            $ventaExistente = $this->repository->getById($id);
            if (!$ventaExistente) {
                return $response->json(['error' => 'Venta no encontrada'], 404); 
            }
            $data = $request->getParsedBody();
            if(empty($data)){
                return $response->json(['error' => 'No se proporcionaron datos para actualizar'], 400);
            }
            $this->validator->validate($data);
            // Determinar si es PUT (completa) o PATCH (parcial)
            $isPutRequest = $request->getMethod() === 'PUT';
            $data = $isPutRequest ? $request->getParsedBody() : $this->filterFields($request->getParsedBody(), $this->camposDefault);
            // Actualizar la venta en el repositorio
            $this->repository->update($id, $data);
            return $response->json(['message' => 'Venta actualizada correctamente'], 200);
        } catch (ValidationException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode()); 
        } catch (DatabaseException $e) {
            return $response->json(['error' => $e->getMessage(), 'details' => $e->getDetails()], $e->getCode()); 
        } catch (\Exception $e) {
            return $response->json(['error' => 'Error inesperado','message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Response $response, $id = null): Response {}

    public function delete(Request $request, Response $response, $id = null): Response {}
}
