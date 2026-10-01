DB::beginTransaction();
$m = \App\Models\ReducedSodiumMenu::first();
try {
    $img = $m->product_image;
    echo "id=" . $m->id . " image=" . $img . PHP_EOL;
    if ($img) {
        \Illuminate\Support\Facades\Storage::disk("public")->delete($img);
        echo "storage delete OK" . PHP_EOL;
    }
    $m->delete();
    echo "model delete OK" . PHP_EOL;
} catch (\Throwable $e) {
    echo "ERROR: " . get_class($e) . " -> " . $e->getMessage() . PHP_EOL;
}
DB::rollBack();
echo "rolled back, no data lost" . PHP_EOL;
