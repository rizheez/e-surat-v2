<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterCategoryRequest;
use App\Http\Requests\StoreLetterNatureRequest;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\StoreArchiveClassificationRequest;
use App\Http\Requests\StoreDispositionInstructionRequest;
use App\Http\Requests\StoreUnitRequest;
use App\Models\ArchiveClassification;
use App\Models\DispositionInstruction;
use App\Models\LetterCategory;
use App\Models\LetterNature;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    public function index(): RedirectResponse
    {
        return to_route('master-data.units.index');
    }

    public function units(): Response
    {
        return Inertia::render('MasterData/Units', [
            ...$this->sharedPayload(),
        ]);
    }

    public function positions(): Response
    {
        return Inertia::render('MasterData/Positions', [
            ...$this->sharedPayload(),
        ]);
    }

    public function categories(): Response
    {
        return Inertia::render('MasterData/Categories', [
            ...$this->sharedPayload(),
        ]);
    }

    public function natures(): Response
    {
        return Inertia::render('MasterData/Natures', [
            ...$this->sharedPayload(),
        ]);
    }

    public function archiveClassifications(): Response
    {
        return Inertia::render('MasterData/ArchiveClassifications', [
            ...$this->sharedPayload(),
        ]);
    }

    public function instructionTemplates(): Response
    {
        return Inertia::render('MasterData/InstructionTemplates', [
            ...$this->sharedPayload(),
        ]);
    }

    private function sharedPayload(): array
    {
        return [
            'units' => Unit::with('parent')->orderBy('nama')->get(),
            'positions' => Position::with('unit')->orderBy('nama')->get(),
            'categories' => LetterCategory::orderBy('kode')->get(),
            'natures' => LetterNature::orderBy('nama')->get(),
            'archiveClassifications' => ArchiveClassification::orderBy('nama')->get(),
            'instructionTemplates' => DispositionInstruction::orderBy('judul')->get(),
        ];
    }

    public function storeUnit(StoreUnitRequest $request): RedirectResponse
    {
        Unit::create([
            ...$request->validated(),
            'is_cross_unit_target' => $request->boolean('is_cross_unit_target'),
        ]);

        return back()->with('success', 'Unit kerja berhasil ditambahkan.');
    }

    public function updateUnit(StoreUnitRequest $request, Unit $unit): RedirectResponse
    {
        $data = $request->validated();

        if ((int) ($data['parent_id'] ?? 0) === $unit->id) {
            return back()->with('error', 'Unit tidak dapat menjadi parent dirinya sendiri.');
        }

        $data['is_cross_unit_target'] = $request->boolean('is_cross_unit_target');
        $unit->update($data);

        return back()->with('success', 'Unit kerja berhasil diperbarui.');
    }

    public function destroyUnit(Unit $unit): RedirectResponse
    {
        return $this->deleteRecord(
            fn () => $unit->delete(),
            'Unit kerja berhasil dihapus.',
            'Unit kerja tidak dapat dihapus karena masih dipakai data lain.'
        );
    }

    public function storePosition(StorePositionRequest $request): RedirectResponse
    {
        Position::create($request->validated());

        return back()->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function updatePosition(StorePositionRequest $request, Position $position): RedirectResponse
    {
        $position->update($request->validated());

        return back()->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroyPosition(Position $position): RedirectResponse
    {
        return $this->deleteRecord(
            fn () => $position->delete(),
            'Jabatan berhasil dihapus.',
            'Jabatan tidak dapat dihapus karena masih dipakai data lain.'
        );
    }

    public function storeCategory(StoreLetterCategoryRequest $request): RedirectResponse
    {
        LetterCategory::create($request->validated());

        return back()->with('success', 'Kategori surat berhasil ditambahkan.');
    }

    public function updateCategory(StoreLetterCategoryRequest $request, LetterCategory $letterCategory): RedirectResponse
    {
        $letterCategory->update($request->validated());

        return back()->with('success', 'Kategori surat berhasil diperbarui.');
    }

    public function destroyCategory(LetterCategory $letterCategory): RedirectResponse
    {
        return $this->deleteRecord(
            fn () => $letterCategory->delete(),
            'Kategori surat berhasil dihapus.',
            'Kategori surat tidak dapat dihapus karena masih dipakai data lain.'
        );
    }

    public function storeNature(StoreLetterNatureRequest $request): RedirectResponse
    {
        LetterNature::create($request->validated());

        return back()->with('success', 'Sifat surat berhasil ditambahkan.');
    }

    public function updateNature(StoreLetterNatureRequest $request, LetterNature $letterNature): RedirectResponse
    {
        $letterNature->update($request->validated());

        return back()->with('success', 'Sifat surat berhasil diperbarui.');
    }

    public function destroyNature(LetterNature $letterNature): RedirectResponse
    {
        return $this->deleteRecord(
            fn () => $letterNature->delete(),
            'Sifat surat berhasil dihapus.',
            'Sifat surat tidak dapat dihapus karena masih dipakai data lain.'
        );
    }

    public function storeArchiveClassification(StoreArchiveClassificationRequest $request): RedirectResponse
    {
        ArchiveClassification::create($request->validated());

        return back()->with('success', 'Klasifikasi arsip berhasil ditambahkan.');
    }

    public function updateArchiveClassification(StoreArchiveClassificationRequest $request, ArchiveClassification $archiveClassification): RedirectResponse
    {
        $archiveClassification->update($request->validated());

        return back()->with('success', 'Klasifikasi arsip berhasil diperbarui.');
    }

    public function destroyArchiveClassification(ArchiveClassification $archiveClassification): RedirectResponse
    {
        return $this->deleteRecord(
            fn () => $archiveClassification->delete(),
            'Klasifikasi arsip berhasil dihapus.',
            'Klasifikasi arsip tidak dapat dihapus karena masih dipakai data lain.'
        );
    }

    public function storeInstructionTemplate(StoreDispositionInstructionRequest $request): RedirectResponse
    {
        DispositionInstruction::create($request->validated());

        return back()->with('success', 'Template instruksi berhasil ditambahkan.');
    }

    public function updateInstructionTemplate(StoreDispositionInstructionRequest $request, DispositionInstruction $instructionTemplate): RedirectResponse
    {
        $instructionTemplate->update($request->validated());

        return back()->with('success', 'Template instruksi berhasil diperbarui.');
    }

    public function destroyInstructionTemplate(DispositionInstruction $instructionTemplate): RedirectResponse
    {
        return $this->deleteRecord(
            fn () => $instructionTemplate->delete(),
            'Template instruksi berhasil dihapus.',
            'Template instruksi tidak dapat dihapus karena masih dipakai data lain.'
        );
    }

    private function deleteRecord(callable $callback, string $successMessage, string $errorMessage): RedirectResponse
    {
        try {
            $callback();
        } catch (QueryException) {
            return back()->with('error', $errorMessage);
        }

        return back()->with('success', $successMessage);
    }
}
