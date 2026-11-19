<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SiteSettingsSeeder::class,
            SeoSeeder::class,
            BannerSeeder::class,
            ContentSeeder::class,
            FeatureSeeder::class,
            DemonstrationSeeder::class,
            FaqSeeder::class,
            BillingModelSeeder::class,
            PricingSeeder::class,
        ]);

        $this->administrator();

        SiteSetting::flush();
    }

    /**
     * Create the first administrator.
     *
     * Credentials come from the environment so no password is hard-coded in
     * the repository. A random password is generated and printed once when
     * ADMIN_PASSWORD is not provided.
     */
    private function administrator(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@hivedfood.test');
        $password = env('ADMIN_PASSWORD');
        $generated = false;

        if (blank($password)) {
            $existing = User::query()->where('email', $email)->first();

            if ($existing) {
                $existing->forceFill(['is_admin' => true])->save();
                $this->command?->info("Administrador existente conservado: {$email}");

                return;
            }

            $password = str()->password(16, symbols: false);
            $generated = true;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrador HIVEDFOOD'),
                'password' => Hash::make($password),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($generated) {
            $this->command?->warn('Administrador creado.');
            $this->command?->line("  Correo:     {$email}");
            $this->command?->line("  Contraseña: {$password}");
            $this->command?->line('  Guarda esta contraseña: no se vuelve a mostrar.');
        } else {
            $this->command?->info("Administrador actualizado: {$email}");
        }
    }
}
