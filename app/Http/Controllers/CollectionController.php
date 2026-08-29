<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Design;
use App\Models\PrintArtwork;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::with(['designs.printArtwork', 'designs' => fn ($q) => $q->withCount('orders')])
            ->withCount('orders')
            ->orderBy('name')
            ->get();

        return view('collections.index', compact('collections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        Collection::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'short_code' => $this->generateShortCode($data['name']),
            'description' => $data['description'] ?? null,
            'active' => true,
        ]);

        return back()->with('success', "Collection \"{$data['name']}\" created!");
    }

    public function update(Request $request, Collection $collection)
    {
        if ($request->has('toggle_active')) {
            $collection->update(['active' => !$collection->active]);
            return back()->with('success', $collection->active ? 'Collection reactivated.' : 'Collection deactivated.');
        }

        $data = $request->validate(['description' => 'nullable|string']);
        $collection->update($data);
        return back()->with('success', 'Collection updated.');
    }

    /**
     * Adding a Design either links it to an EXISTING PrintArtwork (when the
     * person picks "uses same film as..."), sharing that stock, or creates a
     * brand new one 1:1 -- this is the fix for designs like "Who Knows" /
     * "Who Knows Long" splitting film stock that should be shared.
     */
    public function addDesign(Request $request, Collection $collection)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'has_front' => 'required|in:0,1',
            'has_back' => 'required|in:0,1',
            'uses_same_film_as' => 'nullable|exists:designs,id',
        ]);

        if (!empty($data['uses_same_film_as'])) {
            $sourceDesign = Design::findOrFail($data['uses_same_film_as']);
            $printArtworkId = $sourceDesign->print_artwork_id;
        } else {
            $printArtwork = PrintArtwork::create([
                'collection_id' => $collection->id,
                'name' => $data['name'],
                'has_front' => (bool) $data['has_front'],
                'has_back' => (bool) $data['has_back'],
            ]);
            $printArtworkId = $printArtwork->id;
        }

        Design::create([
            'collection_id' => $collection->id,
            'print_artwork_id' => $printArtworkId,
            'name' => $data['name'],
            'active' => true,
        ]);

        return back()->with('success', "Design \"{$data['name']}\" added!");
    }

    public function updateDesign(Request $request, Design $design)
    {
        $design->update(['active' => !$design->active]);
        return back()->with('success', $design->active ? 'Design reactivated.' : 'Design deactivated.');
    }

    private function generateShortCode(string $name): string
    {
        $base = strtoupper(Str::of($name)->replaceMatches('/[^A-Za-z]/', '')->substr(0, 4));
        $base = $base ?: 'COL';

        $code = $base;
        $suffix = 1;
        while (Collection::where('short_code', $code)->exists()) {
            $suffix++;
            $code = $base . $suffix;
        }

        return $code;
    }
}