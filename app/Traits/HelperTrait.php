<?php
namespace App\Traits;

use App\Models\MailSetting;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

trait HelperTrait
{

    protected array $modelMap = [
        'ProductVariant' => ProductVariant::class,
        'Product' => Product::class,
        'Material' => Material::class,
    ];


    public function get_model_by_type($type)
    {
        $modelClass = match ($type) {
            'ProductVariant' => ProductVariant::class, // this should come here for now
            'Product' => Product::class,
            'Material' => Material::class,
            'material' => Material::class,
            "product" => Product::class,
            "variant" => ProductVariant::class,
        };

        return $modelClass;
    }

    public function get_type_by_model($model_type)
    {
        $modelClass = match ($model_type) {
            ProductVariant::class => 'ProductVariant',
            Product::class => 'Product',
            Material::class => 'Material',
            'material' => "Material",
            "product" => 'Product',
            "variant" => 'ProductVariant',
        };

        return $modelClass;
    }


    public function change_items_model_type(
        &$recipes,
        $material_type_name = 'component_type',
        $output_type_name = 'component_type'
    ) {
        foreach ($recipes as $key => &$recipe) {
            if (isset($recipe['material_items'])) {
                foreach ($recipe['material_items'] as &$item) {
                    $item['norm_qty'] = $item['quantity'] ? $item['quantity'] / ($recipe['planned_quantity'] ?? 1) : null;
                    $item[$material_type_name] = $this->get_type_by_model($item[$material_type_name]);
                }
            }

            if (isset($recipe['output_products'])) {
                foreach ($recipe['output_products'] as &$item) {
                    $item['norm_qty'] = $item['qty'] ? $item['qty'] / ($recipe['planned_quantity'] ?? 1) : null;
                    $item[$output_type_name] = $this->get_type_by_model($item[$output_type_name]);
                }
            }
        }
    }

    public function get_true_model_by_type($component_type)
    {
        return match ($component_type) {
            'Product' => Product::query(),
            'ProductVariant' => ProductVariant::query(),
            'Material' => Material::query(),
            default => throw new Exception("Unknown item type: {$component_type}"),
        };
    }

    public function decryptToken($base64Token)
    {
        $key = config('app.encryption.key');
        $iv = config('app.encryption.iv');

        if (empty($key) || empty($iv)) {
            Log::error('Missing encryption credentials');
            return false;
        }

        $encrypted = base64_decode($base64Token);

        if ($encrypted === false) {
            Log::error('Base64 decode failed');
            return false;
        }

        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            Log::error('Decryption failed', [
                'openssl_error' => openssl_error_string()
            ]);
            return false;
        }

        return $decrypted;
    }

    function paginate_collection(
        array $items,
        Request $request,
    ): LengthAwarePaginator {
        $array_to_collection = collect($items);
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 30);

        return new LengthAwarePaginator(
            $array_to_collection->forPage($page, $perPage)->values(),
            $array_to_collection->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }


    public function applyMailSettings()
    {
        $settings = MailSetting::first();

        if (!$settings) {
            throw new Exception('Mail settings not found.');
        }

        config([
            'mail.default' => $settings->mailer,
            'mail.mailers.smtp.host' => $settings->host,
            'mail.mailers.smtp.port' => $settings->port,
            'mail.mailers.smtp.username' => $settings->username,
            'mail.mailers.smtp.password' => $settings->password,
            'mail.mailers.smtp.encryption' => $settings->encryption,
            'mail.from.address' => $settings->from_address,
        ]);
    }
}
