<?php

namespace ktaris\lectorcfdi\traits;

use Underscore\Types\Arrays;

trait ComercioExterior
{
    protected function leerComercioExterior($dataIn)
    {
        $cce = $this->leerNodo('cce11:ComercioExterior', $dataIn);

        if (empty($cce)) {
            return [];
        }

        $dataOut = $this->leerAtributos($cce);

        $dataOut['Emisor'] = $this->leerCceEmisor($cce);

        $dataOut['Receptor'] = $this->leerCceReceptor($cce);

        $dataOut['Mercancias'] = $this->leerCceMercancias($cce);

        return $dataOut;
    }

    protected function leerCceEmisor($dataIn)
    {
        $dataOut = [];

        $nodo = $this->leerNodo('cce11:Emisor', $dataIn);
        if (empty($nodo)) {
            return $dataOut;
        }

        $dataOut = Arrays::merge(
            $this->leerAtributosOpcionales($nodo),
            $this->envolverAtributosEnNuevoNodo($this->leerCceDomicilio($nodo), 'Domicilio')
        );

        return $dataOut;
    }

    protected function leerCceReceptor($dataIn)
    {
        $dataOut = [];

        $nodo = $this->leerNodo('cce11:Receptor', $dataIn);
        if (empty($nodo)) {
            return $dataOut;
        }

        $dataOut = Arrays::merge(
            $this->leerAtributosOpcionales($nodo),
            $this->envolverAtributosEnNuevoNodo($this->leerCceDomicilio($nodo), 'Domicilio')
        );

        return $dataOut;
    }

    protected function leerCceDomicilio($dataIn)
    {
        $dataOut = [];

        $nodo = $this->leerNodo('cce11:Domicilio', $dataIn);
        if (empty($nodo)) {
            return $dataOut;
        }

        return $this->leerAtributosOpcionales($nodo);
    }

    protected function leerCceMercancias($dataIn)
    {
        $dataOut = [];

        $mercancias = $this->leerNodo('cce11:Mercancias', $dataIn);
        $mercancias = $this->leerNodo('cce11:Mercancia', $mercancias);
        $mercancias = $this->adaptarAArreglo($mercancias);

        foreach ($mercancias as $i => $data) {
            $modelData = $this->leerCceMercancia($data);

            $dataOut[$i] = $modelData;
        }

        return $dataOut;
    }

    protected function leerCceMercancia($dataIn)
    {
        $dataOut = [];

        $dataOut = $dataIn['@attributes'];

        return $dataOut;
    }
}
