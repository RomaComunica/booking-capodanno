<div class="mt-4 p-4 border rounded-lg bg-gray-50 flex flex-col items-center justify-center min-h-[100px]">
    <h3 class="text-sm font-medium text-gray-500 mb-4 self-start">Anteprima Widget</h3>
    
    <div class="w-full flex justify-center">
        @if(empty($codice))
            <p class="text-gray-400 italic">Compila il form per vedere l'anteprima</p>
        @else
            {!! $codice !!}
        @endif
    </div>
</div>
