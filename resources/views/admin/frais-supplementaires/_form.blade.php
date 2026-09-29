<div class="form-grid">
    <div class="form-field">
        <label for="libelle">Libellé *</label>
        <input type="text" name="libelle" id="libelle" value="{{ old('libelle', $fraisSupplementaire->libelle ?? '') }}" required>
        @error('libelle')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    <div class="form-row">
        <div class="form-field">
            <label for="montant">Montant (USD) *</label>
            <input type="number" step="0.01" name="montant" id="montant" value="{{ old('montant', $fraisSupplementaire->montant ?? '') }}" required>
            @error('montant')<p class="error-text">{{ $message }}</p>@enderror
        </div>
        <div class="form-field">
            <label for="date_debut">Date début *</label>
            <input type="date" name="date_debut" id="date_debut" value="{{ old('date_debut', $fraisSupplementaire->date_debut ?? '') }}" required>
            @error('date_debut')<p class="error-text">{{ $message }}</p>@enderror
        </div>
        <div class="form-field">
            <label for="date_fin">Date fin *</label>
            <input type="date" name="date_fin" id="date_fin" value="{{ old('date_fin', $fraisSupplementaire->date_fin ?? '') }}" required>
            @error('date_fin')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="form-field">
        <label for="description">Description</label>
        <textarea name="description" id="description" rows="3">{{ old('description', $fraisSupplementaire->description ?? '') }}</textarea>
        @error('description')<p class="error-text">{{ $message }}</p>@enderror
    </div>

    <div class="form-field">
        <label class="checkbox-label">
            <input type="checkbox" name="est_pour_toutes_salles" value="1"
                   {{ old('est_pour_toutes_salles', $fraisSupplementaire->est_pour_toutes_salles ?? false) ? 'checked' : '' }}>
            Appliquer à toutes les salles
        </label>
    </div>

    <div class="form-field" id="salles-field">
        <label>Salles concernées *</label>
        <div class="salles-checkboxes">
            @foreach($salles as $salle)
                <label class="checkbox-label">
                    <input type="checkbox" name="salles[]" value="{{ $salle->id }}"
                           {{ in_array($salle->id, $sallesSelectionnees ?? []) ? 'checked' : '' }}>
                    {{ $salle->nom }} ({{ $salle->section->nom ?? '' }})
                </label>
            @endforeach
        </div>
        @error('salles')<p class="error-text">{{ $message }}</p>@enderror
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkToutes = document.querySelector('input[name="est_pour_toutes_salles"]');
        const sallesField = document.getElementById('salles-field');

        function toggleSalles() {
            if (checkToutes.checked) {
                sallesField.style.display = 'none';
            } else {
                sallesField.style.display = 'block';
            }
        }

        checkToutes.addEventListener('change', toggleSalles);
        toggleSalles();
    });
</script>