<?php

namespace App\Services;

use Core\Contracts\ServiceInterface;
use Core\Exceptions\{AppException, ValidationException};
use Core\Http\{Request, Response};
use Core\Services\BaseService;
use Core\Validation\Validator;
use App\Contracts\VentaServiceInterface;
use App\Repositories\VentaRepository;

class VentaService extends BaseService implements ServiceInterface, VentaServiceInterface
{
    protected $repository;
    protected $validator;
    protected $camposDefault = ['id_venta', 'id_sucursal', 'descuento', 'total', 'status', 'nota', 'mediopago', 'metodo_pago', 'estado', 'id_cliente', 'id_usuario', 'cajero', 'vendedor', 'created_at', 'updated_at'];
    protected $camposSiempreExcluidos = ['tipoventa', 'paga_con', 'devolucion', 'pagacon', 'cambio', 'abono', 'saldo', 'entregado', 'registro', 'mod'];

    public function __construct(VentaRepository $repository, Validator $validator)
    {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    /**
     * Obtiene todas las ventas
     */
    public function readAll(Request $request, Response $response)
    {
        // Sin try-catch - lo maneja el middleware
        $ventas = $this->repository->getAll();
        $ventasFiltradas = $this->filtrarCamposExcluidos($ventas);
        
        return $this->successResponse($response, $ventasFiltradas);
    }

    /**
     * Obtiene una venta específica por ID
     */
    public function read(Request $request, Response $response, $id = null): Response
    {
        // Validar que el ID sea numérico
        if (!is_numeric($id)) {
            throw AppException::invalidOperation("El ID de venta debe ser numérico");
        }

        $data = ['id_venta' => $id];
        $this->validator->validate($data);

        // Buscar la venta en el repositorio
        $venta = $this->repository->getById($id);
        
        if (!$venta) {
            throw AppException::notFound("Venta");
        }

        // Formatear el resultado
        $ventaFormateada = $this->filtrarCamposExcluidos($venta);
        
        return $this->successResponse($response, $ventaFormateada);
    }

    /**
     * Crea una nueva venta
     */
    public function create(Request $request, Response $response): Response
    {
        $data = $request->input();
        
        if (empty($data)) {
            throw AppException::invalidOperation("No se proporcionaron datos para la venta");
        }
        
        $this->validator->validate($data);
        
        // Crear la venta en el repositorio
        try {
            $idVenta = $this->repository->create($data);
        } catch (\Exception $e) {
            throw AppException::invalidOperation("Error al crear la venta: " . $e->getMessage());
        }
        
        return $this->messageResponse(
            $response, 
            'Venta creada correctamente',
            201
        );
    }

    /**
     * Actualiza una venta existente
     */
    public function update(Request $request, Response $response, $id): Response
    {
        if (!is_numeric($id)) {
            throw AppException::invalidOperation("El ID de venta debe ser numérico");
        }
        
        $ventaExistente = $this->repository->getById($id);
        
        if (!$ventaExistente) {
            throw AppException::notFound("Venta");
        }
        
        $data = $request->input();
        
        if (empty($data)) {
            throw AppException::invalidOperation("No se proporcionaron datos para actualizar");
        }
        
        $this->validator->validate($data);
        
        // Determinar si es PUT (completa) o PATCH (parcial)
        $isPutRequest = $request->method() === 'PUT';
        $dataToUpdate = $isPutRequest 
            ? $data 
            : array_intersect_key($data, array_flip($this->camposDefault));
        
        // Actualizar la venta en el repositorio
        try {
            $this->repository->update($id, $dataToUpdate);
        } catch (\Exception $e) {
            throw AppException::invalidOperation("Error al actualizar la venta: " . $e->getMessage());
        }
        
        return $this->messageResponse($response, 'Venta actualizada correctamente');
    }

    /**
     * Elimina una venta existente
     */
    public function delete(Request $request, Response $response, $id): Response
    {
        if (!is_numeric($id)) {
            throw AppException::invalidOperation("El ID de venta debe ser numérico");
        }
        
        $ventaExistente = $this->repository->getById($id);
        
        if (!$ventaExistente) {
            throw AppException::notFound("Venta");
        }
        
        try {
            $this->repository->delete($id);
        } catch (\Exception $e) {
            throw AppException::invalidOperation("Error al eliminar la venta: " . $e->getMessage());
        }
        
        return $this->messageResponse($response, 'Venta eliminada correctamente');
    }
    
    /**
     * Verifica si una venta tiene pagos asociados
     */
    public function verificarPagosAsociados($id): bool
    {
        $pagos = $this->repository->getPagosByVentaId($id);
        
        if (count($pagos) > 0) {
            throw AppException::invalidOperation(
                "No se puede eliminar la venta porque tiene pagos asociados", 
                409, // Conflict
                'VENTA_HAS_PAYMENTS'
            );
        }
        
        return true;
    }
}