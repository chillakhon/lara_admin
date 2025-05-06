<?php
namespace App\Traits;
use Bepsvpt\Blurhash\Facades\BlurHash;
use File;
use Intervention\Image\Facades\Image;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Spatie\Image\Image as SpatieImage;
use App\Models\Image as ImageModel;

trait ImageTrait
{
    public function save_images(
        $image = null,
        $item_type = null,
        $item_id = null,
        $position = null,
    ) {
        $img_names = ['original', 'lg', 'md', 'sm']; // remove names then
        $img_sizes = [1024, 512, 256, 128]; // remove sizes then

        $extension = $image->getClientOriginalExtension();
        $randomInt = crc32(uniqid());

        // $ids = [];

        $manager = new ImageManager(new ImagickDriver());

        $full_path = storage_path('app/public/products/');

        if (!File::exists($full_path)) {
            File::makeDirectory($full_path, 0755, true); // recursive = true
        }

        for ($i = 0; $i < 4; $i++) {
            $image_name = "{$img_names[$i]}_" . "image_" . "{$item_id}_" . $randomInt . '.' . $extension;

            $image_path = $full_path . $image_name;
            // Read image
            $img = $manager->read($image);

            // Resize
            $img->resize($img_sizes[$i], $img_sizes[$i]);

            // Save
            $img->save($image_path);

        }

        $blur_hash_image = BlurHash::encode($image);
        $image_name_table = "image_" . "{$item_id}_" . $randomInt . '.' . $extension;


        ImageModel::create([
            'item_id' => $item_id,
            "item_type" => $item_type,
            "blur_hash" => $blur_hash_image,
            "path" => $image_name_table,
            'order' => $position,
            'is_main' => (!is_null($position) && $position == 0) ? true : false,
        ]);
    }
}
