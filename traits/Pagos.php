<?php

namespace ktaris\lectorcfdi\traits;

use Underscore\Types\Arrays;

trait Pagos
{
    protected function leerPagos($dataIn)
    {
        $pagos = $this->leerNodo('pago10:Pagos', $dataIn);

        if (empty($pagos)) {
            return [];
        }

        $dataOut = $this->leerAtributos($pagos);

        $dataOut['Pagos'] = $this->leerArregloDePagos($pagos);

        return $dataOut;
    }

    protected function leerArregloDePagos($dataIn)
    {
        $dataOut = [];

        $pagos = $this->leerNodo('pago10:Pago', $dataIn);
        $pagos = $this->adaptarAArreglo($pagos);

        foreach ($pagos as $i => $data) {
            $modelData = $this->leerPagosPago($data);

            $dataOut[$i] = $modelData;
        }

        return $dataOut;
    }

    protected function leerPagosPago($dataIn)
    {
        $dataOut = [];

        $dataOut = $dataIn['@attributes'];

        $doctosRelacionados = $this->leerPagosDoctosRelacionados($dataIn);
        if (!empty($doctosRelacionados)) {
            $dataOut['DoctosRelacionados'] = $doctosRelacionados;
        }

        return $dataOut;
    }

    protected function leerPagosDoctosRelacionados($dataIn)
    {
        $dataOut = [];

        $doctos = $this->leerNodo('pago10:DoctoRelacionado', $dataIn);
        $doctos = $this->adaptarAArreglo($doctos);
        if (empty($doctos)) {
            return $dataOut;
        }

        foreach ($doctos as $i => $data) {
            $modelData = $this->leerPagosDoctoRelacionado($data);

            $dataOut[$i] = $modelData;
        }

        return $dataOut;
    }

    protected function leerPagosDoctoRelacionado($dataIn)
    {
        $dataOut = [];

        $dataOut = $dataIn['@attributes'];

        return $dataOut;
    }
}
