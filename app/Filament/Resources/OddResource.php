<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OddResource\Pages;
use App\Models\AdminActionLog;
use App\Models\MatchGame;
use App\Models\Odd;
use App\Models\OddHistory;
use App\Services\AnalysisService;
use App\Services\AdminPasswordVerifier;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OddResource extends Resource
{
    protected static ?string $model = Odd::class;
    protected static ?string $navigationLabel = 'Cotes';
    protected static ?string $modelLabel = 'cote';
    protected static ?string $pluralModelLabel = 'cotes';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('match.equipe_a')->label('Domicile')->searchable(),
            TextColumn::make('match.equipe_b')->label('Extérieur')->searchable(),
            TextColumn::make('bookmaker')->searchable(),
            TextColumn::make('cote_1')->label('1'),
            TextColumn::make('cote_X')->label('X'),
            TextColumn::make('cote_2')->label('2'),
            TextColumn::make('source')->badge(),
            TextColumn::make('updated_at')->label('Actualisée')->since(),
        ])->emptyStateHeading('Aucune cote')->emptyStateDescription('Ajoutez des cotes à un match ou synchronisez les données externes.')
            ->headerActions([
            CreateAction::make()->label('Ajouter des cotes')->form(self::formSchema())->using(function (array $data): Odd {
                self::verifyPassword($data);
                $raison = $data['raison'] ?? null;
                $estimates = $data['probabilites_estimees'] ?? null;
                unset($data['admin_password'], $data['raison'], $data['probabilites_estimees']);
                return DB::transaction(function () use ($data, $raison, $estimates): Odd {
                    $odd = Odd::create([...$data, 'source' => 'manuel']);
                    app(AnalysisService::class)->update($odd, $estimates);
                    self::recordHistory($odd, null, $data, $raison);
                    self::log('ajout_cote', $odd, null, $odd->toArray(), $raison);
                    return $odd;
                });
            }),
        ])->actions([
            EditAction::make()->form(self::formSchema(false))->using(function (Odd $record, array $data): Odd {
                self::verifyPassword($data);
                $old = $record->only(['bookmaker', 'cote_1', 'cote_X', 'cote_2']);
                $raison = $data['raison'] ?? null;
                $estimates = $data['probabilites_estimees'] ?? $record->analyses()->first()?->probabilites_estimees;
                unset($data['admin_password'], $data['raison'], $data['probabilites_estimees']);
                DB::transaction(function () use ($record, $old, $data, $raison, $estimates): void {
                    $record->update([...$data, 'source' => 'manuel', 'is_modified' => true]);
                    app(AnalysisService::class)->update($record, $estimates);
                    self::recordHistory($record, $old, $data, $raison);
                    self::log('modif_cote', $record, $old, $record->toArray(), $raison);
                });
                return $record;
            }),
            DeleteAction::make()->form(self::confirmationSchema())->before(function (DeleteAction $action, Odd $record): void {
                self::verifyPassword($action->getFormData());
                self::log('suppr_cote', $record, $record->toArray(), null, $action->getFormData()['raison'] ?? null);
            }),
        ]);
    }

    private static function formSchema(bool $withMatch = true): array
    {
        return [
            ...($withMatch ? [Select::make('match_id')->label('Match')->options(MatchGame::query()->orderBy('date_match')->get()->mapWithKeys(fn ($match) => [$match->id => $match->equipe_a.' - '.$match->equipe_b])->all())->searchable()->required()] : []),
            TextInput::make('bookmaker')->required()->maxLength(100),
            TextInput::make('cote_1')->label('Cote 1')->numeric()->minValue(1.001)->required(),
            TextInput::make('cote_X')->label('Cote X')->numeric()->minValue(1.001)->required(),
            TextInput::make('cote_2')->label('Cote 2')->numeric()->minValue(1.001)->required(),
            TextInput::make('probabilites_estimees.1')->label('Probabilité estimée 1 (%)')->numeric()->minValue(0)->maxValue(100)->required(fn (Get $get): bool => self::hasEstimates($get)),
            TextInput::make('probabilites_estimees.X')->label('Probabilité estimée X (%)')->numeric()->minValue(0)->maxValue(100)->required(fn (Get $get): bool => self::hasEstimates($get)),
            TextInput::make('probabilites_estimees.2')->label('Probabilité estimée 2 (%)')->numeric()->minValue(0)->maxValue(100)->required(fn (Get $get): bool => self::hasEstimates($get)),
            ...self::confirmationSchema(),
        ];
    }

    private static function confirmationSchema(): array
    {
        return [TextInput::make('admin_password')->label('Mot de passe administrateur')->password()->required()->dehydrated(), Textarea::make('raison')->label('Raison')->maxLength(2000)];
    }

    private static function hasEstimates(Get $get): bool
    {
        return collect(['1', 'X', '2'])->contains(fn (string $issue): bool => filled($get('probabilites_estimees.'.$issue)));
    }

    private static function verifyPassword(array $data): void
    {
        $result = app(AdminPasswordVerifier::class)->verify(auth()->user(), $data['admin_password'] ?? null, request()->ip());
        if ($result !== 'valid') {
            throw ValidationException::withMessages(['admin_password' => 'Mot de passe administrateur invalide.']);
        }
    }

    private static function recordHistory(Odd $odd, ?array $old, array $new, ?string $reason): void
    {
        OddHistory::create(['odd_id' => $odd->id, 'ancienne_cote_1' => $old['cote_1'] ?? null, 'nouvelle_cote_1' => $new['cote_1'] ?? null, 'ancienne_cote_X' => $old['cote_X'] ?? null, 'nouvelle_cote_X' => $new['cote_X'] ?? null, 'ancienne_cote_2' => $old['cote_2'] ?? null, 'nouvelle_cote_2' => $new['cote_2'] ?? null, 'modifie_par' => auth()->id(), 'raison' => $reason]);
    }

    private static function log(string $action, Odd $odd, ?array $old, ?array $new, ?string $reason): void
    {
        AdminActionLog::create(['user_id' => auth()->id(), 'action' => $action, 'table_cible' => 'odds', 'enregistrement_id' => $odd->id, 'anciennes_valeurs' => $old, 'nouvelles_valeurs' => $new, 'raison' => $reason, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOdds::route('/')];
    }
}