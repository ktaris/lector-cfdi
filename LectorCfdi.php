<?php

/**
 * @copyright Copyright (c) 2017 Carlos Ramos
 * @package ktaris-csd
 * @version 0.1.0
 */

namespace ktaris\lectorcfdi;

use Yii;
use LaLit\XML2Array;
use ktaris\lectorcfdi\traits\ComercioExterior;
use ktaris\lectorcfdi\traits\Pagos;
use ktaris\lectorcfdi\LectorCfdiException;
use ktaris\cfdi\CFDI;

class LectorCfdi
{
    use ComercioExterior;
    use Pagos;

    /**
     * @var {@link ktaris\cfdi\CFDI} objeto en PHP para su uso con
     * el framework Yii2.
     */
    protected $_cfdi;
    /**
     * @var array arreglo de datos para instanciar una clase diferente
     * de CFDI. Utilizada principalmente para adicionar propiedades a
     * la representación impresa.
     */
    protected $_cfdi_config;
    /**
     * @var SimpleXMLElement objeto que lee el XML, de cadena o de
     * archivo, que se usa internamente para cargar los datos a
     * cada uno de los modelos.
     */
    protected $_arreglo;

    public function __construct($config = [])
    {
        $this->_cfdi_config = $config;
    }

    public function leerDesdeCadena($xmlStr)
    {
        $this->_arreglo = XML2Array::createArray($xmlStr);
        $nodoComprobante = $this->leerNodoRequerido('cfdi:Comprobante');

        $this->_arreglo = $nodoComprobante;

        $this->_cfdi = $this->crearObjetoCfdi();

        $datos = $this->_arreglo['@attributes'];
        $datos['Emisor'] = $this->leerAtributosDeNodoRequerido('cfdi:Emisor');
        $datos['Receptor'] = $this->leerAtributosDeNodoRequerido('cfdi:Receptor');
        $datos['Conceptos'] = $this->leerConceptos();

        $impuestos = $this->leerImpuestos($datos);
        if (!empty($impuestos)) {
            $datos['Impuestos'] = $impuestos;
        }

        $complementos = $this->leerComplementos();
        if (!empty($complementos)) {
            $datos['Complemento'] = $complementos;
        }

        $this->_cfdi->load($datos);

        return $this->_cfdi;
    }

    // ==================================================================
    //
    // Métodos protegidos para tratamiento de información de los nodos.
    //
    // ------------------------------------------------------------------

    protected function leerConceptos()
    {
        $dataOut = [];

        $conceptos = $this->leerNodoRequerido('cfdi:Conceptos');
        $conceptos = $this->leerNodoRequerido('cfdi:Concepto', $conceptos);
        $conceptos = $this->adaptarAArreglo($conceptos);

        foreach ($conceptos as $i => $data) {
            $concepto = $this->leerConcepto($data);

            $dataOut[$i] = $concepto;
        }

        return $dataOut;
    }

    protected function leerConcepto($dataIn)
    {
        $dataOut = [];

        $dataOut = $dataIn['@attributes'];

        $impuestos = $this->leerConceptoImpuestos($dataIn);
        if (!empty($impuestos)) {
            $dataOut['Impuestos'] = $impuestos;
        }

        return $dataOut;
    }

    protected function leerConceptoImpuestos($dataIn)
    {
        $dataOut = [];

        $impuestos = $this->leerNodo('cfdi:Impuestos', $dataIn);
        if (empty($impuestos)) {
            return $dataOut;
        }

        $traslados = $this->leerConceptoTraslados($impuestos);
        if (!empty($traslados)) {
            $dataOut['Traslados'] = $traslados;
        }

        $retenciones = $this->leerConceptoRetenciones($impuestos);
        if (!empty($retenciones)) {
            $dataOut['Retenciones'] = $retenciones;
        }

        return $dataOut;
    }

    protected function leerConceptoTraslados($dataIn)
    {
        $dataOut = [];

        $impuestos = $this->leerNodo('cfdi:Traslados', $dataIn);
        if (empty($impuestos)) {
            return $dataOut;
        }

        $impuestosArray = $this->adaptarAArreglo($this->leerNodo('cfdi:Traslado', $impuestos));
        foreach ($impuestosArray as $i => $impuesto) {
            $dataOut[$i] = $this->leerAtributos($impuesto);
        }

        return $dataOut;
    }

    protected function leerConceptoRetenciones($dataIn)
    {
        $dataOut = [];

        $impuestos = $this->leerNodo('cfdi:Retenciones', $dataIn);
        if (empty($impuestos)) {
            return $dataOut;
        }

        $impuestosArray = $this->adaptarAArreglo($this->leerNodo('cfdi:Retencion', $impuestos));
        foreach ($impuestosArray as $i => $impuesto) {
            $dataOut[$i] = $this->leerAtributos($impuesto);
        }

        return $dataOut;
    }

    protected function leerImpuestos($dataIn)
    {
        $dataOut = [];

        $impuestos = $this->leerNodo('cfdi:Impuestos');
        if (empty($impuestos)) {
            return $dataOut;
        }

        $dataOut = $this->leerAtributos($impuestos);

        $traslados = $this->leerTraslados($impuestos);
        if (!empty($traslados)) {
            $dataOut['Traslados'] = $traslados;
        }

        $retenciones = $this->leerRetenciones($impuestos);
        if (!empty($retenciones)) {
            $dataOut['Retenciones'] = $retenciones;
        }

        return $dataOut;
    }

    protected function leerTraslados($dataIn)
    {
        $dataOut = [];

        $impuestos = $this->leerNodo('cfdi:Traslados', $dataIn);
        if (empty($impuestos)) {
            return $dataOut;
        }

        $dataOut = $this->leerArregloDeDatos('cfdi:Traslado', $impuestos);

        return $dataOut;
    }

    protected function leerRetenciones($dataIn)
    {
        $dataOut = [];

        $impuestos = $this->leerNodo('cfdi:Retenciones', $dataIn);
        if (empty($impuestos)) {
            return $dataOut;
        }

        $dataOut = $this->leerArregloDeDatos('cfdi:Retencion', $impuestos);

        return $dataOut;
    }

    // ==================================================================
    //
    // Lectura de complementos.
    //
    // ------------------------------------------------------------------

    protected function leerComplementos()
    {
        $dataOut = [];

        $complementos = $this->leerNodo('cfdi:Complemento');
        if (empty($complementos)) {
            return $dataOut;
        }

        $nodo = $this->leerTimbreFiscalDigital($complementos);
        if (!empty($nodo)) {
            $dataOut['TimbreFiscalDigital'] = $nodo;
        }

        $nodo = $this->leerComercioExterior($complementos);
        if (!empty($nodo)) {
            $dataOut['ComercioExterior'] = $nodo;
        }

        $nodo = $this->leerPagos($complementos);
        if (!empty($nodo)) {
            $dataOut['Pagos'] = $nodo;
        }

        return $dataOut;
    }

    protected function leerTimbreFiscalDigital($dataIn)
    {
        return $this->leerAtributosDeNodo('tfd:TimbreFiscalDigital', $dataIn);
    }

    // ==================================================================
    //
    // Métodos protegidos para tratamiento de datos, funciones comunes.
    //
    // ------------------------------------------------------------------

    protected function leerArregloDeDatos($nombreDeNodo, $dataIn)
    {
        $dataOut = [];

        $nodos = $this->leerNodo($nombreDeNodo, $dataIn);
        $nodosArray = $this->adaptarAArreglo($nodos);

        foreach ($nodosArray as $i => $nodo) {
            $dataOut[$i] = $this->leerAtributos($nodo);
        }

        return $dataOut;
    }

    /**
     * El convertidor de XML2Array hace de nodos que pueden ser varios uno
     * solo cuando sólo está presente una instancia. No obstante, para mayor
     * compatibilidad se crea un arreglo de un elemento, para poder iterar
     * sobre la lista, aunque sea uno solo.
     * Para esto se utiliza esta función, para determinar si se tuvo un solo
     * elemento de algo que queremos interpretar como arreglo.
     *
     * @param  array $dataIn datos de entrada, ya sea uno o varios.
     *
     * @return array         datos de salida, en lista.
     */
    protected function adaptarAArreglo($dataIn)
    {
        $dataOut = [];

        if (!empty($dataIn) && !empty($dataIn['@attributes'])) {
            $dataOut[0] = $dataIn;
        } else {
            $dataOut = $dataIn;
        }

        return $dataOut;
    }

    protected function leerAtributosDeNodoRequerido($nombreDeNodo, $nodoInicial = null)
    {
        $nodo = $this->leerNodoRequerido($nombreDeNodo, $nodoInicial);

        return $nodo['@attributes'];
    }

    protected function leerAtributosDeNodo($nombreDeNodo, $nodoInicial = null)
    {
        $nodo = $this->leerNodo($nombreDeNodo, $nodoInicial);

        if (empty($nodo)) {
            return $nodo;
        }

        return $nodo['@attributes'];
    }

    protected function leerNodoRequerido($nombreDeNodo, $nodoInicial = null)
    {
        $nodo = $this->leerNodo($nombreDeNodo, $nodoInicial);

        if (empty($nodo)) {
            throw new LectorCfdiException("El XML no contiene el nodo requerido $nombreDeNodo.");
        }

        return $nodo;
    }

    protected function leerNodo($nombreDeNodo, $nodoInicial = null)
    {
        if ($nodoInicial === null) {
            $nodoInicial = $this->_arreglo;
        }

        if (empty($nodoInicial[$nombreDeNodo])) {
            return [];
        }

        return $nodoInicial[$nombreDeNodo];
    }

    /**
     * Esta función lee los atributos de un nodo, y asume que siempre tiene que tener atributos el elemento.
     * Es decir, el nombre más correcto sería "leerAtributosRequeridos".
     *
     * @param  array $dataIn arreglo proveniente de un XML.
     *
     * @return array atributos correspondientes según lo que tenía el XML.
     */
    protected function leerAtributos($dataIn)
    {
        if (empty($dataIn['@attributes'])) {
            throw new LectorCfdiException('No hay atributos para leer.');
        }

        return $dataIn['@attributes'];
    }

    /**
     * Esta función lee atributos opcionales, en nodos que puede que no tengan ni siquiera un elemento obligatorio.
     *
     * @param  array $dataIn arreglo proveniente de un XML.
     *
     * @return array atributos correspondientes según lo que tenía el XML.
     */
    protected function leerAtributosOpcionales($dataIn)
    {
        if (empty($dataIn['@attributes'])) {
            return [];
        }

        return $dataIn['@attributes'];
    }

    protected function envolverAtributosEnNuevoNodo($dataIn, $nombreDeNodo)
    {
        if (empty($dataIn)) {
            return $dataIn;
        }

        return [$nombreDeNodo => $dataIn];
    }

    // ==================================================================
    //
    // Métodos de procesamiento interno, propios de la clase.
    //
    // ------------------------------------------------------------------

    private function crearObjetoCfdi()
    {
        if (empty($this->_cfdi_config)) {
            return new CFDI;
        } else {
            return Yii::createObject($this->_cfdi_config);
        }
    }
}
