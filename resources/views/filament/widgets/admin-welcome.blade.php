<x-filament-widgets::widget>
    <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Bonjour {{ auth()->user()->name }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Voici l’activité actuelle de votre espace ParisFOOT.</p>
        </div>
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</span>
    </div>
</x-filament-widgets::widget>