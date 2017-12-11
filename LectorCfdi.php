<?php

/**
 * @copyright Copyright (c) 2017 Carlos Ramos
 * @package ktaris-csd
 * @version 0.1.0
 */

namespace ktaris\lectorcfdi;

use ktaris\lectorcfdi\LectorCfdiException;
use ktaris\cfdi\CFDI;

class LectorCfdi
{
    /**
     * @var {@link ktaris\cfdi\CFDI} objeto en PHP para su uso con
     * el framework Yii2.
     */
    protected $_cfdi;
    /**
     * @var SimpleXMLElement objeto que lee el XML, de cadena o de
     * archivo, que se usa internamente para cargar los datos a
     * cada uno de los modelos.
     */
    protected $_cfdi_xml_obj;

    public function leerDesdeCadena($xmlStr)
    {
        $this->_cfdi_xml_obj = new \SimpleXMLElement($xmlStr);

        $this->_cfdi = new CFDI;

        $this->leerNodosDeCfdi();

        return $this->_cfdi;
    }

    // ==================================================================
    //
    // Funciones de procesamiento interno, para leer nodo por nodo.
    //
    // ------------------------------------------------------------------

    protected function leerNodosDeCfdi()
    {
        $datos = [];
        // Comprobante.
        $nodo = $this->leerNodoComprobante();
        $datosTmp = $this->envolverAtributosDeModelo('Comprobante', $nodo);
        $datos = array_merge($datos, $datosTmp);
        // Emisor.
        $nodo = $this->leerNodoEmisor();
        $datosTmp = $this->envolverAtributosDeModelo('Emisor', $nodo);
        $datos = array_merge($datos, $datosTmp);
        // Receptor.
        $nodo = $this->leerNodoReceptor();
        $datosTmp = $this->envolverAtributosDeModelo('Receptor', $nodo);
        $datos = array_merge($datos, $datosTmp);
        // Conceptos.
        $nodo = $this->leerNodoConceptos();
        $datosTmp = $this->envolverAtributosDeArreglo('Conceptos', $nodo);
        $datos = array_merge($datos, $datosTmp);

        $this->_cfdi->load($datos);

        return $datos;
    }

    protected function leerNodoComprobante()
    {
        return $this->leerNodo('//cfdi:Comprobante');
    }

    protected function leerNodoEmisor()
    {
        return $this->leerNodo('//cfdi:Comprobante/cfdi:Emisor');
    }

    protected function leerNodoReceptor()
    {
        return $this->leerNodo('//cfdi:Comprobante/cfdi:Receptor');
    }

    protected function leerNodoConceptos()
    {
        $nodo = $this->leerNodo('//cfdi:Comprobante/cfdi:Conceptos');

        return $this->leerNodos($nodo, '//cfdi:Concepto');
    }

    // ==================================================================
    //
    // Funciones de procesamiento interno, comunes.
    //
    // ------------------------------------------------------------------

    protected function leerNodo($xpath)
    {
        $nodo = $this->_cfdi_xml_obj->xpath($xpath);

        if (empty($nodo)) {
            throw new LectorCfdiException('No se encontró el nodo '.$xpath.'.');
        }

        $nodo = $nodo[0];

        return $nodo;
    }

    protected function leerNodos($nodo, $xpath)
    {
        $nodos = [];
        foreach ($nodo->xpath($xpath) as $i => $n) {
            $nodos[] = $n;
        }

        return $nodos;
    }

    /**
     * Carga los datos de un nodo de XML al modelo que lo representa
     * en PHP, utilizando el nombre de clase del objeto para crear
     * el arreglo de carga de datos.
     * @param  mixed $modelo modelo a recibir los datos.
     * @param  SimpleXMLElement $nodo   nodo dentro del XML del CFDI.
     * @return boolean         bandera para saber si se cargaron los datos.
     */
    protected function cargarDatos($modelo, $nodo)
    {
        $datos = $this->envolverAtributosDeModelo($modelo->nombreDeClase, $nodo);

        return $modelo->load($datos);
    }

    /**
     * Se encarga de envolver los atributos de un nodo en un arreglo
     * que puede ser entendido por los modelos de Yii2 para su carga.
     *
     * @param  string           $nombreDeModelo Nombre del modelo en ktaris\yii2-cfdi.
     * @param  SimpleXMLElement $nodoXml        Nodo de XML con los atributos.
     * @return array            arreglo de datos con el nombre de modelo.
     */
    protected function envolverAtributosDeModelo($nombreDeModelo, $nodoXml)
    {
        return [$nombreDeModelo => current($nodoXml->attributes())];
    }

    /**
     * Se encarga de envolver los atributos de un nodo en un arreglo
     * que puede ser entendido por los modelos de Yii2 para su carga.
     *
     * @param  string           $nombreDeContenedor Nombre del elemento contenedor en el CFDI.
     * @param  SimpleXMLElement $nodoXml            Nodo de XML con los atributos.
     * @return array            arreglo de datos con el nombre de modelo.
     */
    protected function envolverAtributosDeArreglo($nombreDeContenedor, $nodosXml)
    {
        $datos = [];
        foreach ($nodosXml as $i => $n) {
            $datos[] = current($n->attributes());
        }

        return [$nombreDeContenedor => $datos];
    }
}
