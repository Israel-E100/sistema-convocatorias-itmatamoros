<form action="{{ $action }}" method="POST" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div>
        <label for="titulo" class="mb-1 block text-sm font-semibold text-gray-700">Título</label>
        <input id="titulo" name="titulo" value="{{ old('titulo', $convocatoria->titulo ?? '') }}" required maxlength="255"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="tipo_personal" class="mb-1 block text-sm font-semibold text-gray-700">Tipo de personal</label>
            <select id="tipo_personal" name="tipo_personal" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
                @foreach (['Docente', 'No docente'] as $type)
                    <option value="{{ $type }}" @selected(old('tipo_personal', $convocatoria->tipo_personal ?? 'Docente') === $type)>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="tipo_concurso" class="mb-1 block text-sm font-semibold text-gray-700">Tipo de concurso</label>
            <select id="tipo_concurso" name="tipo_concurso" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
                @foreach (['Abierta', 'Cerrada'] as $type)
                    <option value="{{ $type }}" @selected(old('tipo_concurso', $convocatoria->tipo_concurso ?? 'Abierta') === $type)>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
        </div>
        @foreach ([
            'categoria_nivel' => 'Categoría o nivel',
            'plaza_disponible' => 'Plazas disponibles',
            'jornada_horario' => 'Jornada u horario',
        ] as $field => $label)
            <div>
                <label for="{{ $field }}" class="mb-1 block text-sm font-semibold text-gray-700">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $convocatoria->$field ?? '') }}" required maxlength="150"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
            </div>
        @endforeach
        <div>
            <label for="plaza_id" class="mb-1 block text-sm font-semibold text-gray-700">Código de plaza asociado (opcional)</label>
            <select id="plaza_id" name="plaza_id"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
                <option value="">Sin plaza específica</option>
                @foreach ($plazasPorTiempo as $tipoTiempo => $plazas)
                    <optgroup label="{{ $tipoTiempo }}">
                        @foreach ($plazas as $plaza)
                            <option value="{{ $plaza->id }}"
                                @selected((string) old('plaza_id', $convocatoria->plaza_id ?? '') === (string) $plaza->id)>
                                {{ $plaza->codigo }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Una plaza asociada permite abrir esta convocatoria desde el tablero.</p>
        </div>
        <div>
            <label for="salario" class="mb-1 block text-sm font-semibold text-gray-700">Remuneración mensual (MXN)</label>
            <input id="salario" type="number" name="salario" value="{{ old('salario', $convocatoria->salario ?? '') }}" min="0" step="0.01"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
        </div>
        <div>
            <label for="fecha_inicio" class="mb-1 block text-sm font-semibold text-gray-700">Fecha de inicio</label>
            <input id="fecha_inicio" type="date" name="fecha_inicio"
                value="{{ old('fecha_inicio', isset($convocatoria) ? $convocatoria->fecha_inicio->format('Y-m-d') : '') }}" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
        </div>
        <div>
            <label for="fecha_fin" class="mb-1 block text-sm font-semibold text-gray-700">Fecha de cierre</label>
            <input id="fecha_fin" type="date" name="fecha_fin"
                value="{{ old('fecha_fin', isset($convocatoria) ? $convocatoria->fecha_fin->format('Y-m-d') : '') }}" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
        </div>
        <div>
            <label for="estatus" class="mb-1 block text-sm font-semibold text-gray-700">Estatus</label>
            <select id="estatus" name="estatus" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
                @foreach (['Activa', 'Borrador', 'Cerrada'] as $status)
                    <option value="{{ $status }}" @selected(old('estatus', $convocatoria->estatus ?? 'Activa') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label for="requisitos_especificos" class="mb-1 block text-sm font-semibold text-gray-700">Requisitos</label>
        <textarea id="requisitos_especificos" name="requisitos_especificos" rows="5" maxlength="10000"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">{{ old('requisitos_especificos', $convocatoria->requisitos_especificos ?? '') }}</textarea>
    </div>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('dictaminador.convocatorias.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
        <button type="submit" class="rounded-lg bg-rose-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">{{ $submitLabel }}</button>
    </div>
</form>
