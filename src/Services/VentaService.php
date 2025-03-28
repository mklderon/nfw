<?php

namespace App\Services;

use Core\Http\{Request, Response};
use Core\Services\BaseService;
use Core\Contracts\ServiceInterface;
use Core\Exceptions\{ValidationException, DatabaseException, AppException};
use Core\Validation\Validator;
use App\Repositories\VentaRepository;
use App\Contracts\VentaServiceInterface;

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
        try {
            $ventas = $this->repository->getAll();
            $ventasFiltradas = $this->filtrarCamposExcluidos($ventas);
            
            return $this->successResponse($response, $ventasFiltradas);
        } catch (\Exception $e) {
            throw $e; // Dejemos que el middleware ErrorHandlerMiddleware lo maneje
        }
    }

    /**
     * Obtiene una venta específica por ID
     */
    public function read(Request $request, Response $response, $id = null): Response
    {
        // Validar que el ID sea numérico
        if (!is_numeric($id)) {
            throw new AppException("El ID de venta debe ser numérico", 400);
        }

        // Buscar la venta en el repositorio
        $venta = $this->repository->getById($id);
        
        if (!$venta) {
            throw new AppException("Venta no encontrada", 404);
        }

        // Formatear el resultado - usar el método filtrarCamposExcluidos directamente
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
            throw new ValidationException(['general' => ["No se proporcionaron datos para la venta"]]);
        }
        
        $this->validator->validate($data);
        
        // Crear la venta en el repositorio
        $idVenta = $this->repository->create($data);
        
        return $this->messageResponse(
            $response, 
            'Venta creada correctamente', 
            201,
            ['id_venta' => $idVenta]
        );
    }

    /**
     * Actualiza una venta existente
     */
    public function update(Request $request, Response $response, $id): Response
    {
        if (!is_numeric($id)) {
            throw new ValidationException(['id' => ["El ID de venta debe ser numérico"]]);
        }
        
        $ventaExistente = $this->repository->getById($id);
        
        if (!$ventaExistente) {
            throw new AppException("Venta no encontrada", 404);
        }
        
        $data = $request->input();
        
        if (empty($data)) {
            throw new ValidationException(['general' => ["No se proporcionaron datos para actualizar"]]);
        }
        
        $this->validator->validate($data);
        
        // Actualizar la venta en el repositorio
        $this->repository->update($id, $data);
        
        return $this->messageResponse($response, 'Venta actualizada correctamente');
    }

    /**
     * Elimina una venta existente (realmente la marca como cancelada)
     */
    public function delete(Request $request, Response $response, $id): Response
    {
        if (!is_numeric($id)) {
            throw new ValidationException(['id' => ["El ID de venta debe ser numérico"]]);
        }
        
        $ventaExistente = $this->repository->getById($id);
        
        if (!$ventaExistente) {
            throw new AppException("Venta no encontrada", 404);
        }
        
        // Verificar si la venta tiene pagos asociados
        $pagos = $this->repository->getPagosByVentaId($id);
        
        if (count($pagos) > 0) {
            throw new AppException(
                "No se puede eliminar la venta porque tiene pagos asociados", 
                409 // Conflict
            );
        }
        
        // En lugar de eliminar, marcamos como cancelada
        $this->repository->cambiarEstado($id, 'cancelada');
        
        return $this->messageResponse($response, 'Venta marcada como cancelada correctamente');
    }
    
    /**
     * Filtra los campos excluidos de un array de datos o de un registro individual
     * 
     * @param array $data Los datos a filtrar
     * @param array|null $camposExcluidos Campos a excluir (opcional)
     * @return array Los datos filtrados sin los campos excluidos
     */
    protected function filtrarCamposExcluidos($data, ?array $camposExcluidos = null)
    {
        // Usar los campos excluidos proporcionados o los definidos en la clase
        $camposParaExcluir = $camposExcluidos ?? $this->camposSiempreExcluidos;
        
        // Si no hay campos para excluir, devolver los datos sin cambios
        if (empty($camposParaExcluir)) {
            return $data;
        }
        
        // Si es un array multidimensional (múltiples registros)
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $key => $registro) {
                $data[$key] = $this->filtrarCamposExcluidos($registro, $camposParaExcluir);
            }
            return $data;
        } 
        // Si es un array simple (un registro)
        else {
            foreach ($camposParaExcluir as $campo) {
                if (array_key_exists($campo, $data)) {
                    unset($data[$campo]);
                }
            }
            return $data;
        }
    }
}