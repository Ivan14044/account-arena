<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PurchasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Purchase $purchase, ?string $guestEmail = null): bool
    {
        // Если пользователь авторизован, он должен быть владельцем
        if ($user && $purchase->user_id === $user->id) {
            return true;
        }

        // Если это гость, проверяем guest_email
        if (!$purchase->user_id && $purchase->guest_email) {
            if ($guestEmail && strtolower(trim($guestEmail)) === strtolower($purchase->guest_email)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can download the purchase data.
     */
    public function download(?User $user, Purchase $purchase, ?string $guestEmail = null): bool
    {
        return $this->view($user, $purchase, $guestEmail);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Purchase $purchase): bool
    {
        return $user->id === $purchase->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Purchase $purchase): bool
    {
        return false; // Запрещаем удаление покупок пользователям
    }

    /**
     * Determine whether the user can cancel the purchase.
     */
    public function cancel(?User $user, Purchase $purchase, ?string $guestEmail = null): bool
    {
        if ($purchase->status !== Purchase::STATUS_PROCESSING) {
            return false;
        }

        return $this->view($user, $purchase, $guestEmail);
    }
}
