<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // $this->call([
        //     UsersSeeder::class,
        //     // RolesAndPermissionsSeeder::class // Закомментируем или удалим, так как роли создаются в UsersSeeder
        //     UnitsTableSeeder::class,
        //     CategorySeeder::class,
        //     CostCategorySeeder::class,
        //     LeadTypeSeeder::class,
        // ]);

        $this->call([
            CartSeeder::class,
            CategorySeeder::class,
            ClientSeeder::class,
            ColorCategorySeeder::class,
            ColorOptionSeeder::class,
            ColorOptionValueSeeder::class,
            ColorSeeder::class,
            ComponentReservationSeeder::class,
                // ContentBlockSeeder::class,
            CostCategorySeeder::class,
            FieldsSeeder::class,
            ImageableSeeder::class,
            ImageSeeder::class,
            UnitsTableSeeder::class,
                // InventorySystemSeeder::class, should resolve
            LeadTypeSeeder::class,
            OptionSeeder::class,
            OptionValueSeeder::class,
            OrderitemSeeder::class,
                // OrderSeeder::class, // should resolve
            PermissionSeeder::class,
            ProductComponentSeeder::class,
            ProductionBatchSeeder::class,
            ProductionComponentConsumptionSeeder::class,
            ProductSeeder::class,
            ProductVariantSeeder::class,
            PromoCodeSeeder::class,
            PromoCodeUsageSeeder::class,
            RecipeItemSeeder::class,
            RecipeSeeder::class,
            RolesAndPermissionsSeeder::class,
            RoleSeeder::class,
            ShipmentStatusSeeder::class,
            UsersSeeder::class,
            ReviewAttributeSeeder::class,
            ReviewResponseSeeder::class,
            ProductsColorSeeder::class,
        ]);
    }
}
