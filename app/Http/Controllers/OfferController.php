<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class OfferController extends Controller
{
    use AuthorizesRequests;

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Offer $offer): RedirectResponse
    {
        $this->authorize('delete', $offer);

        $offer->delete();

        return redirect()->route('offers.index');
    }

    /**
     * Restore an archived offer.
     */
    public function restore(Offer $offer): RedirectResponse
    {
        $this->authorize('restore', $offer);

        $offer->restore();

        return redirect()->route('offers.show', $offer);
    }

    /**
     * Permanently delete an archived offer.
     */
    public function forceDelete(Offer $offer): RedirectResponse
    {
        $this->authorize('forceDelete', $offer);

        $offer->forceDelete();

        return redirect()->route('offers.archived');
    }
}
