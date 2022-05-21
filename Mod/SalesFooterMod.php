<?php
/*
 * Copyright (C) 2022 Joe Nilson <joenilson@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\fsRepublicaDominicana\Mod;

use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipo;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoPago;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoAnulacion;
use FacturaScripts\Dinamic\Model\Cliente;

class SalesFooterMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData, User $user)
    {
        // TODO: Implement apply() method.
    }

    public function applyBefore(SalesDocument &$model, array $formData, User $user)
    {
        // TODO: Implement applyBefore() method.
        if ($model->modelClassName() === 'FacturaCliente') {
            $model->tipocomprobante = isset($formData['tipocomprobante']) ? (string)$formData['tipocomprobante'] : $model->tipocomprobante;
            $model->ncffechavencimiento = isset($formData['ncffechavencimiento']) ? (string)$formData['ncffechavencimiento'] : $model->ncffechavencimiento;
            $model->ncftipopago = isset($formData['ncftipopago']) ? (string)$formData['ncftipopago'] : $model->ncftipopago;
            $model->ncftipomovimiento = isset($formData['ncftipomovimiento']) ? (string)$formData['ncftipomovimiento'] : $model->ncftipomovimiento;
            $model->ncftipoanulacion = isset($formData['ncftipoanulacion']) ? (string)$formData['ncftipoanulacion'] : $model->ncftipoanulacion;
        }
    }

    public function assets(): void
    {
        // TODO: Implement assets() method.
    }

    public function newFields(): array
    {
        // TODO: Implement newFields() method.
        return ['tipocomprobante', 'ncffechavencimiento', 'ncftipopago', 'ncftipomovimiento', 'ncftipoanulacion'];
    }

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        if ($model->modelClassName() === 'FacturaCliente') {
            switch ($field) {
                case "tipocomprobante":
                    return $this->tipoComprobante($i18n, $model);
                case "ncffechavencimiento":
                    return $this->ncfFechaVencimiento($i18n, $model);
                case "ncftipopago":
                    return $this->ncfTipoPago($i18n, $model);
                case "ncftipomovimiento":
                    return $this->ncfTipoMovimiento($i18n, $model);
                case "ncftipoanulacion":
                    return $this->ncfTipoAnulacion($i18n, $model);
                default:
                    return null;
            }
        }
        return null;
    }

    private static function infoCliente($codcliente)
    {
        $cliente = new Cliente();
        $actualCliente = $cliente->get($codcliente);
        if ('' !== $actualCliente) {
            return $actualCliente;
        }
        return null;
    }

    private static function tipoComprobante(Translator $i18n, SalesDocument $model): string
    {
        $tipoComprobante = NCFTipo::allVentas();
        if (count($tipoComprobante) === 0) {
            return '';
        }

        $cliente = self::infoCliente($model->codcliente);

        if ($model->tipocomprobante) {
            $invoiceTipoComprobante = $model->tipocomprobante;
        } else {
            $invoiceTipoComprobante = ($cliente->tipocomprobante !== '') ? $cliente->tipocomprobante : "02";
        }

        $options = ['<option value="">------</option>'];
        foreach ($tipoComprobante as $row) {
            $options[] = ($row->tipocomprobante === $invoiceTipoComprobante) ?
                '<option value="' . $row->tipocomprobante . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->tipocomprobante . '">' . $row->descripcion . '</option>';
        }

        $attributes = ($model->editable || $model->numero2 === '') ? 'name="tipocomprobante" required=""' : 'disabled=""';
        return '<div class="col-sm-3">'
            . '<div class="form-group">'
            .  $i18n->trans('tipocomprobante')
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfTipoPago(Translator $i18n, SalesDocument $model): string
    {
        $NCFTipoPago = new NCFTipoPago();
        $tipoPago = $NCFTipoPago->findAllByTipopago('01');
        if (count($tipoPago) === 0) {
            return '';
        }

        $cliente = self::infoCliente($model->codcliente);

        if ($model->ncftipopago) {
            $invoiceTipoPago = $model->ncftipopago;
        } else {
            $invoiceTipoPago = ($cliente->ncftipopago !== '') ? $cliente->ncftipopago : "17";
        }

        $options = ['<option value="">------</option>'];
        foreach ($tipoPago as $row) {
            $options[] = ($row->codigo === $invoiceTipoPago) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="ncftipopago" required=""' : 'disabled=""';
        return '<div class="col-sm-2">'
            . '<div class="form-group">'
            .  $i18n->trans('ncf-payment-type')
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfTipoMovimiento(Translator $i18n, SalesDocument $model): string
    {
        $NCFTipoMovimiento = new NCFTipoMovimiento();
        $tipoMovimiento = $NCFTipoMovimiento->findAllByTipomovimiento('VEN');
        if (count($tipoMovimiento) === 0) {
            return '';
        }

        $cliente = self::infoCliente($model->codcliente);

        $invoiceTipoMovimiento = ($model->ncftipomovimiento) ?: "1";

        $options = ['<option value="">------</option>'];
        foreach ($tipoMovimiento as $row) {
            $options[] = ($row->codigo === $invoiceTipoMovimiento) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="ncftipomovimiento" required=""' : 'disabled=""';
        return '<div class="col-sm-3">'
            . '<div class="form-group">'
            .  $i18n->trans('ncf-movement-type')
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfTipoAnulacion(Translator $i18n, SalesDocument $model): string
    {
        $NCFTipoAnulacion = new NCFTipoAnulacion();
        $tipoAnulacion = $NCFTipoAnulacion->all();
        if (count($tipoAnulacion) === 0) {
            return '';
        }

        $invoiceTipoAnulacion = ($model->ncftipoanulacion) ?: "";

        $options = ['<option value="">------</option>'];
        foreach ($tipoAnulacion as $row) {
            $options[] = ($row->codigo === $invoiceTipoAnulacion) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="ncftipoanulacion"' : 'disabled=""';
        return '<div class="col-sm-2">'
            . '<div class="form-group">'
            .  $i18n->trans('ncf-cancellation-type')
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfFechaVencimiento(Translator $i18n, SalesDocument $model): string
    {
        $attributes = ($model->editable && $model->numero2 === '') ? 'name="ncffechavencimiento"' : 'disabled=""';
        return '<div class="col-sm-2">'
            . '<div class="form-group">' . $i18n->trans('due-date')
            . '<input type="date" ' . $attributes . ' value="' . date('Y-m-d', strtotime($model->ncffechavencimiento)) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }
}