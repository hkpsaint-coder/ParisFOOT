<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MatchGameResource\Pages;
use App\Models\AdminActionLog;
use App\Models\MatchGame;
use App\Services\AdminPasswordVerifier;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class MatchGameResource extends Resource
{
    protected static ?string $model = MatchGame::class;
    protected static ?string $navigationLabel = 'Matchs';
    protected static ?string $modelLabel = 'match';
    protected static ?string $pluralModelLabel = 'matchs';
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('equipe_a')->label('Domicile')->searchable()->sortable(),
            TextColumn::make('equipe_b')->label('Extérieur')->searchable(),
            TextColumn::make('date_match')->label('Date')->dateTime()->sortable(),
            TextColumn::make('competition')->searchable(),
            TextColumn::make('source')->badge(),
            TextColumn::make('odds_count')->counts('odds')->label('Bookmakers'),
        ])->emptyStateHeading('Aucun match')->emptyStateDescription('Ajoutez un match ou lancez une synchronisation des cotes.')
            ->filters([
            SelectFilter::make('source')->options(['api' => 'API', 'manuel' => 'Manuel']),
        ])->headerActions([
            CreateAction::make()->label('Ajouter un match')->form(self::formSchema())->using(function (array $data): MatchGame {
                self::verifyPassword($data);
                $raison = $data['raison'] ?? null;
                unset($data['admin_password'], $data['raison']);
                $match = MatchGame::create([...$data, 'source' => 'manuel', 'created_by' => auth()->id()]);
                self::log('ajout_match', $match->id, null, $match->toArray(), $raison);

                return $match;
            }),
        ])->actions([
            EditAction::make()->form(self::formSchema())->using(function (MatchGame $record, array $data): MatchGame {
                self::verifyPassword($data);
                $old = $record->only(['equipe_a', 'equipe_b', 'date_match', 'competition']);
                $raison = $data['raison'] ?? null;
                unset($data['admin_password'], $data['raison']);
                $record->update($data);
                self::log('modif_match', $record->id, $old, $record->only(array_keys($old)), $raison);

                return $record;
            }),
            DeleteAction::make()->form(self::confirmationSchema())->before(function (DeleteAction $action, MatchGame $record): void {
                self::verifyPassword($action->getFormData());
                self::log('suppr_match', $record->id, $record->toArray(), null, $action->getFormData()['raison'] ?? null);
            }),
        ]);
    }

    private static function formSchema(): array
    {
        return [
            TextInput::make('equipe_a')->label('Équipe à domicile')->required()->maxLength(150),
            TextInput::make('equipe_b')->label('Équipe à l’extérieur')->required()->maxLength(150),
            DateTimePicker::make('date_match')->label('Date du match')->required(),
            TextInput::make('competition')->required()->maxLength(150),
            ...self::confirmationSchema(),
        ];
    }

    private static function confirmationSchema(): array
    {
        return [TextInput::make('admin_password')->label('Mot de passe administrateur')->password()->required()->dehydrated(), Textarea::make('raison')->label('Raison')->maxLength(2000)];
    }

    private static function verifyPassword(array $data): void
    {
        $result = app(AdminPasswordVerifier::class)->verify(auth()->user(), $data['admin_password'] ?? null, request()->ip());
        if ($result !== 'valid') {
            throw ValidationException::withMessages(['admin_password' => 'Mot de passe administrateur invalide.']);
        }
    }

    private static function log(string $action, int $id, ?array $old, ?array $new, ?string $reason): void
    {
        AdminActionLog::create(['user_id' => auth()->id(), 'action' => $action, 'table_cible' => 'matches', 'enregistrement_id' => $id, 'anciennes_valeurs' => $old, 'nouvelles_valeurs' => $new, 'raison' => $reason, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMatchGames::route('/')];
    }
}