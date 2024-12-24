<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Material;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\MaterialOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class MaterialOrderRelationManager extends RelationManager
{
    protected static string $relationship = 'materialOrder';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_id') // Campo para order_id
                    ->default(fn() => $this->ownerRecord->id) // Establece el valor por defecto al ID de la orden padre
                    ->label('Orden ID')
                    ->readOnly(), // Hacerlo solo lectura
                Forms\Components\Select::make('material_id')
                    ->relationship('material', 'name') // Asegúrate de que esta relación esté definida correctamente en el modelo Material
                    ->label('Material')
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_id')
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Orden ID'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad'),
                Tables\Columns\TextColumn::make('material.name')
                    ->label('Material'),
                Tables\Columns\TextColumn::make('material.unit_price')
                    ->label('Costo por Unidad')
                    ->money('USD'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->after(
                    function (MaterialOrder $materialOrder): void {
                        // Obtén el material correspondiente
                        $material = Material::find($materialOrder->material_id);

                        if ($material) {
                            // Resta la cantidad del inventario
                            $newQuantity = $material->available_quantity - $materialOrder->quantity;

                            if ($newQuantity < 0) {
                                throw new \Exception('No hay suficiente stock para completar esta orden.');
                            }

                            // Actualiza la cantidad disponible del material
                            $material->available_quantity = $newQuantity;
                            $material->save();
                        }
                    }
                ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->after(
                    function (MaterialOrder $materialOrder): void {
                        // Ajustar la cantidad en el inventario al editar
                        // Primero, recupera el material original para calcular la diferencia
                        $originalQuantity = MaterialOrder::find($materialOrder->id)->quantity;
                        $this->adjustMaterialQuantity($materialOrder, $materialOrder->quantity - $originalQuantity);
                    }
                ),
                Tables\Actions\DeleteAction::make()->after(
                    function (MaterialOrder $materialOrder): void {
                        // Al eliminar, suma la cantidad al inventario
                        $this->adjustMaterialQuantity($materialOrder, $materialOrder->quantity);
                    }
                ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function adjustMaterialQuantity(MaterialOrder $materialOrder, int $amount): void
    {
        // Obtén el material correspondiente
        $material = Material::find($materialOrder->material_id);

        if ($material) {
            // Asegúrate de que no haya stock negativo
            if ($amount < 0 && ($material->available_quantity + $amount) < 0) {
                throw new \Exception('No hay suficiente stock para completar esta orden.');
            }

            // Ajusta la cantidad disponible del material
            $material->available_quantity += $amount;
            $material->save();
        }
    }
}