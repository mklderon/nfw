<?php

namespace App\Traits;

trait DataTransformer
{
    /**
     * Filtra los campos excluidos de un array de datos o de un registro individual
     * 
     * @param array $data Los datos a filtrar
     * @param array|null $camposExcluidos Campos a excluir (opcional si ya están definidos en la clase)
     * @return array Los datos filtrados sin los campos excluidos
     */
    protected function filtrarCamposExcluidos($data, ?array $camposExcluidos = null)
    {
        // Usar los campos excluidos proporcionados o los definidos en la clase
        $camposParaExcluir = $camposExcluidos ?? $this->getCamposExcluidos();
        
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
    
    /**
     * Obtiene los campos que deben ser excluidos.
     * Este método debe ser implementado por la clase que utiliza el trait,
     * o puede ser sobrescrito para personalizar el comportamiento.
     * 
     * @return array
     */
    protected function getCamposExcluidos(): array
    {
        // Por defecto, intenta obtener los campos excluidos de la propiedad de la clase
        return $this->camposSiempreExcluidos ?? [];
    }
    
    /**
     * Convierte a minúsculas un string o todos los valores string dentro de un array
     * 
     * @param string|array $data El string o array a transformar
     * @return string|array Los datos con strings convertidos a minúsculas
     */
    protected function convertirAMinusculas($data)
    {
        // Si es un string, simplemente convertirlo y devolverlo
        if (is_string($data)) {
            return strtolower($data);
        }
        
        // Si es un array, recorrerlo y convertir cada valor string
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = strtolower($value);
                } elseif (is_array($value)) {
                    // Recursivamente procesar arrays anidados
                    $data[$key] = $this->convertirAMinusculas($value);
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Aplica transformaciones a los datos según sean necesarias
     * 
     * @param mixed $data Los datos a transformar
     * @param array $opciones Opciones de transformación (ej: ['convertirMinusculas' => true])
     * @return mixed Los datos transformados
     */
    protected function transformarDatos($data, array $opciones = [])
    {
        $resultado = $data;
        
        // Aplicar conversión a minúsculas si la opción está habilitada
        if (isset($opciones['convertirMinusculas']) && $opciones['convertirMinusculas'] === true) {
            $resultado = $this->convertirAMinusculas($resultado);
        }
        
        // Filtrar campos excluidos si la opción está habilitada
        if (isset($opciones['filtrarCampos']) && $opciones['filtrarCampos'] === true) {
            $camposEspecificos = $opciones['camposExcluidos'] ?? null;
            $resultado = $this->filtrarCamposExcluidos($resultado, $camposEspecificos);
        }
        
        return $resultado;
    }
}