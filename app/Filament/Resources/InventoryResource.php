<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource\RelationManagers;
use App\Models\Inventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Set;


class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $recordTitleAttribute = 'Inventario';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $navigationGroup = 'Almacén';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('material_id')
                    ->relationship('material', 'name', function ($query) {
                        // Filtrar materiales que no están en la tabla inventories
                        $query->whereNotIn('id', function ($subquery) {
                            $subquery->select('material_id')->from('inventories');
                        });
                    })
                    ->label('Material')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        // Lógica para obtener la cantidad actual
                        $material = \App\Models\Material::find($state);
                        $set('current_quantity', $material ? $material->available_quantity : null);
                    }),

                Forms\Components\TextInput::make('current_quantity')
                    ->label('Cantidad Disponible')
                    ->required()
                    ->readOnly()
                    ->numeric(),
                Forms\Components\TextInput::make('min_quantity')
                    ->label('Cantidad mínima requerida')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('material.name')
                    ->label('Material')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_quantity')
                    ->label('Cantidad Disponible')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_quantity')
                    ->label('Cantidad mínima requerida')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInventories::route('/'),
        ];
    }
}
