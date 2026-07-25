<?php

namespace App\Services;

use App\Http\Requests\IdentityVerification\SubmitVerificationRequest;
use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class IdentityVerificationService
{
    /**
     * Soumet une demande de vérification d'identité.
     * Sécurité : les fichiers sont stockés dans un dossier privé
     * non accessible publiquement (storage/app/verifications).
     */
    public function submit(SubmitVerificationRequest $request, User $user): IdentityVerification
    {
        $idDocumentPath = $this->storeFile(
            $request->file('id_document'),
            "verifications/{$user->id}"
        );

        $selfiePath = null;
        if ($request->hasFile('selfie')) {
            $selfiePath = $this->storeFile(
                $request->file('selfie'),
                "verifications/{$user->id}"
            );
        }

        $verification = IdentityVerification::create([
            'user_id' => $user->id,
            'id_document_type' => $request->input('id_document_type'),
            'id_document_path' => $idDocumentPath,
            'selfie_path' => $selfiePath,
            'status' => 'pending',
        ]);

        // Mettre à jour le statut de l'utilisateur
        $user->update(['identity_status' => 'pending']);

        return $verification;
    }

    public function getStatus(User $user): ?IdentityVerification
    {
        return IdentityVerification::where('user_id', $user->id)
            ->latest()
            ->first();
    }

    public function hasPendingVerification(User $user): bool
    {
        return IdentityVerification::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    private function storeFile(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'local');
    }
}
