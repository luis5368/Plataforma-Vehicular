<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\TheftReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class VehicleDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sistema.gt')->first();
        if (!$admin) {
            $admin = User::first();
        }

        // Datos ficticios distribuidos en Guatemala
        $vehiclesData = [
            // Guatemala (Departamento)
            ['vin' => '1HGCM82633A000001', 'plate' => 'P100AAA', 'brand' => 'Toyota', 'model' => 'Hilux', 'year' => 2020, 'color' => 'Blanco', 'department' => 'Guatemala', 'municipality' => 'Guatemala', 'zone' => 'Zona 1', 'theft_latitude' => 14.6225, 'theft_longitude' => -90.5273, 'theft_address' => '6a Avenida, Zona 1, Ciudad de Guatemala', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Vehículo sustraído por asaltantes armados frente al Parque Central.'],
            ['vin' => '1HGCM82633A000002', 'plate' => 'P200BBB', 'brand' => 'Nissan', 'model' => 'Frontier', 'year' => 2019, 'color' => 'Negro', 'department' => 'Guatemala', 'municipality' => 'Guatemala', 'zone' => 'Zona 10', 'theft_latitude' => 14.5995, 'theft_longitude' => -90.5153, 'theft_address' => 'Avenida La Reforma, Zona 10', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Robo en estacionamiento de centro comercial.'],
            ['vin' => '1HGCM82633A000003', 'plate' => 'P300CCC', 'brand' => 'Hyundai', 'model' => 'Tucson', 'year' => 2021, 'color' => 'Gris', 'department' => 'Guatemala', 'municipality' => 'Guatemala', 'zone' => 'Zona 4', 'theft_latitude' => 14.6089, 'theft_longitude' => -90.5204, 'theft_address' => 'Calzada Roosevelt, Zona 4', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Sustraído mientras el propietario estaba en restaurante.'],
            ['vin' => '1HGCM82633A000004', 'plate' => 'P400DDD', 'brand' => 'Mitsubishi', 'model' => 'L200', 'year' => 2018, 'color' => 'Rojo', 'department' => 'Guatemala', 'municipality' => 'Mixco', 'zone' => 'Zona 1', 'theft_latitude' => 14.6333, 'theft_longitude' => -90.6067, 'theft_address' => 'Boulevard Los Próceres, Mixco', 'status' => 'reportado', 'theft_status' => 'resuelto', 'theft_desc' => 'Recuperado por la PNC 3 días después del reporte.'],
            ['vin' => '1HGCM82633A000005', 'plate' => 'P500EEE', 'brand' => 'Ford', 'model' => 'Ranger', 'year' => 2022, 'color' => 'Azul', 'department' => 'Guatemala', 'municipality' => 'Villa Nueva', 'zone' => 'Zona 2', 'theft_latitude' => 14.5256, 'theft_longitude' => -90.5878, 'theft_address' => 'Carretera a El Salvador, Villa Nueva', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Asalto en ruta, conductores amenazados con arma de fuego.'],
            ['vin' => '1HGCM82633A000006', 'plate' => 'P600FFF', 'brand' => 'Chevrolet', 'model' => 'D-Max', 'year' => 2020, 'color' => 'Blanco', 'department' => 'Guatemala', 'municipality' => 'Guatemala', 'zone' => 'Zona 18', 'theft_latitude' => 14.6500, 'theft_longitude' => -90.4833, 'theft_address' => 'Calzada Atanasio Tzul, Zona 18', 'status' => 'activo', 'theft_status' => null, 'theft_desc' => null],
            
            // Sacatepéquez
            ['vin' => '1HGCM82633A000007', 'plate' => 'S100AAA', 'brand' => 'Toyota', 'model' => '4Runner', 'year' => 2021, 'color' => 'Negro', 'department' => 'Sacatepéquez', 'municipality' => 'Antigua Guatemala', 'zone' => 'Casco Urbano', 'theft_latitude' => 14.5586, 'theft_longitude' => -90.7339, 'theft_address' => '5a Avenida Norte, Antigua Guatemala', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Robado de estacionamiento frente a hotel turístico.'],
            ['vin' => '1HGCM82633A000008', 'plate' => 'S200BBB', 'brand' => 'Jeep', 'model' => 'Wrangler', 'year' => 2019, 'color' => 'Verde', 'department' => 'Sacatepéquez', 'municipality' => 'San Lucas Sacatepéquez', 'zone' => 'Centro', 'theft_latitude' => 14.5833, 'theft_longitude' => -90.6167, 'theft_address' => 'Carretera Interamericana, San Lucas', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Sustraído en carretera, conductores amedrentados.'],
            ['vin' => '1HGCM82633A000009', 'plate' => 'S300CCC', 'brand' => 'Mazda', 'model' => 'BT-50', 'year' => 2020, 'color' => 'Plata', 'department' => 'Sacatepéquez', 'municipality' => 'Ciudad Vieja', 'zone' => 'Centro', 'theft_latitude' => 14.5333, 'theft_longitude' => -90.7333, 'theft_address' => 'Parque Central, Ciudad Vieja', 'status' => 'activo', 'theft_status' => null, 'theft_desc' => null],
            
            // Escuintla
            ['vin' => '1HGCM82633A000010', 'plate' => 'E100AAA', 'brand' => 'Isuzu', 'model' => 'D-Max', 'year' => 2021, 'color' => 'Blanco', 'department' => 'Escuintla', 'municipality' => 'Escuintla', 'zone' => 'Zona 1', 'theft_latitude' => 14.3050, 'theft_longitude' => -90.7850, 'theft_address' => 'Boulevard Tecún Umán, Escuintla', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Robo en carretera al Pacífico, zona de alto riesgo.'],
            ['vin' => '1HGCM82633A000011', 'plate' => 'E200BBB', 'brand' => 'Toyota', 'model' => 'Land Cruiser', 'year' => 2022, 'color' => 'Negro', 'department' => 'Escuintla', 'municipality' => 'Palín', 'zone' => 'Centro', 'theft_latitude' => 14.4333, 'theft_longitude' => -90.6167, 'theft_address' => 'Carretera a San Vicente Pacaya, Palín', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Asalto en ruta, vehículo de alto valor.'],
            ['vin' => '1HGCM82633A000012', 'plate' => 'E300CCC', 'brand' => 'Nissan', 'model' => 'NP300', 'year' => 2019, 'color' => 'Gris', 'department' => 'Escuintla', 'municipality' => 'Santa Lucía Cotzumalguapa', 'zone' => 'Zona 1', 'theft_latitude' => 14.3333, 'theft_longitude' => -91.0167, 'theft_address' => 'Frente al ingenio, Santa Lucía Cotzumalguapa', 'status' => 'activo', 'theft_status' => null, 'theft_desc' => null],
            
            // Quetzaltenango
            ['vin' => '1HGCM82633A000013', 'plate' => 'Q100AAA', 'brand' => 'Ford', 'model' => 'Explorer', 'year' => 2020, 'color' => 'Azul', 'department' => 'Quetzaltenango', 'municipality' => 'Quetzaltenango', 'zone' => 'Zona 1', 'theft_latitude' => 14.8333, 'theft_longitude' => -91.5167, 'theft_address' => 'Avenida El Calvario, Zona 1, Xela', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Robado de estacionamiento en el centro de la ciudad.'],
            ['vin' => '1HGCM82633A000014', 'plate' => 'Q200BBB', 'brand' => 'Chevrolet', 'model' => 'Trailblazer', 'year' => 2021, 'color' => 'Rojo', 'department' => 'Quetzaltenango', 'municipality' => 'Salcajá', 'zone' => 'Centro', 'theft_latitude' => 14.8667, 'theft_longitude' => -91.5500, 'theft_address' => 'Parque Central, Salcajá', 'status' => 'reportado', 'theft_status' => 'resuelto', 'theft_desc' => 'Recuperado en operativo de la PNC.'],
            ['vin' => '1HGCM82633A000015', 'plate' => 'Q300CCC', 'brand' => 'Hyundai', 'model' => 'Creta', 'year' => 2022, 'color' => 'Blanco', 'department' => 'Quetzaltenango', 'municipality' => 'Quetzaltenango', 'zone' => 'Zona 3', 'theft_latitude' => 14.8400, 'theft_longitude' => -91.5200, 'theft_address' => 'Boulevard Xela, Zona 3', 'status' => 'activo', 'theft_status' => null, 'theft_desc' => null],
            
            // Alta Verapaz
            ['vin' => '1HGCM82633A000016', 'plate' => 'V100AAA', 'brand' => 'Toyota', 'model' => 'Prado', 'year' => 2021, 'color' => 'Negro', 'department' => 'Alta Verapaz', 'municipality' => 'Cobán', 'zone' => 'Zona 1', 'theft_latitude' => 15.4667, 'theft_longitude' => -90.3667, 'theft_address' => 'Parque Central, Cobán', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Sustraído en zona turística, posible banda organizada.'],
            ['vin' => '1HGCM82633A000017', 'plate' => 'V200BBB', 'brand' => 'Mitsubishi', 'model' => 'Montero', 'year' => 2019, 'color' => 'Gris', 'department' => 'Alta Verapaz', 'municipality' => 'San Pedro Carchá', 'zone' => 'Centro', 'theft_latitude' => 15.3833, 'theft_longitude' => -90.2833, 'theft_address' => 'Carretera a Cobán, San Pedro Carchá', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Asalto en carretera, zona rural.'],
            ['vin' => '1HGCM82633A000018', 'plate' => 'V300CCC', 'brand' => 'Nissan', 'model' => 'Pathfinder', 'year' => 2020, 'color' => 'Blanco', 'department' => 'Alta Verapaz', 'municipality' => 'Cobán', 'zone' => 'Zona 2', 'theft_latitude' => 15.4700, 'theft_longitude' => -90.3700, 'theft_address' => 'Barrio San Cristóbal, Cobán', 'status' => 'activo', 'theft_status' => null, 'theft_desc' => null],
            
            // Izabal
            ['vin' => '1HGCM82633A000019', 'plate' => 'I100AAA', 'brand' => 'Ford', 'model' => 'F-150', 'year' => 2022, 'color' => 'Negro', 'department' => 'Izabal', 'municipality' => 'Puerto Barrios', 'zone' => 'Zona 1', 'theft_latitude' => 15.7167, 'theft_longitude' => -88.5833, 'theft_address' => 'Frente al puerto, Puerto Barrios', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Robado en zona portuaria, posible traslado hacia frontera.'],
            ['vin' => '1HGCM82633A000020', 'plate' => 'I200BBB', 'brand' => 'Chevrolet', 'model' => 'Silverado', 'year' => 2021, 'color' => 'Rojo', 'department' => 'Izabal', 'municipality' => 'Livingston', 'zone' => 'Centro', 'theft_latitude' => 15.8333, 'theft_longitude' => -88.7500, 'theft_address' => 'Muelle principal, Livingston', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Sustraído cerca del área turística.'],
            ['vin' => '1HGCM82633A000021', 'plate' => 'I300CCC', 'brand' => 'Toyota', 'model' => 'Tacoma', 'year' => 2020, 'color' => 'Azul', 'department' => 'Izabal', 'municipality' => 'Morales', 'zone' => 'Centro', 'theft_latitude' => 15.4833, 'theft_longitude' => -89.1167, 'theft_address' => 'Carretera CA-9, Morales', 'status' => 'activo', 'theft_status' => null, 'theft_desc' => null],
            
            // Petén
            ['vin' => '1HGCM82633A000022', 'plate' => 'T100AAA', 'brand' => 'Jeep', 'model' => 'Grand Cherokee', 'year' => 2021, 'color' => 'Verde', 'department' => 'Petén', 'municipality' => 'Flores', 'zone' => 'Isla de Flores', 'theft_latitude' => 16.9167, 'theft_longitude' => -89.8833, 'theft_address' => 'Calle Principal, Isla de Flores', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Robado en zona turística, posible traslado a México.'],
            ['vin' => '1HGCM82633A000023', 'plate' => 'T200BBB', 'brand' => 'Mazda', 'model' => 'CX-5', 'year' => 2022, 'color' => 'Blanco', 'department' => 'Petén', 'municipality' => 'Santa Elena', 'zone' => 'Centro', 'theft_latitude' => 16.9333, 'theft_longitude' => -89.9000, 'theft_address' => 'Avenida Central, Santa Elena', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Asalto en estacionamiento de hotel.'],
            ['vin' => '1HGCM82633A000024', 'plate' => 'T300CCC', 'brand' => 'Hyundai', 'model' => 'Santa Fe', 'year' => 2020, 'color' => 'Negro', 'department' => 'Petén', 'municipality' => 'San Benito', 'zone' => 'Zona 1', 'theft_latitude' => 16.9200, 'theft_longitude' => -89.8900, 'theft_address' => 'Carretera a Flores, San Benito', 'status' => 'activo', 'theft_status' => null, 'theft_desc' => null],
            
            // Huehuetenango
            ['vin' => '1HGCM82633A000025', 'plate' => 'H100AAA', 'brand' => 'Isuzu', 'model' => 'MU-X', 'year' => 2021, 'color' => 'Plata', 'department' => 'Huehuetenango', 'municipality' => 'Huehuetenango', 'zone' => 'Zona 1', 'theft_latitude' => 15.3167, 'theft_longitude' => -91.4667, 'theft_address' => 'Parque Central, Huehuetenango', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Sustraído en centro de la ciudad.'],
            ['vin' => '1HGCM82633A000026', 'plate' => 'H200BBB', 'brand' => 'Ford', 'model' => 'Ranger', 'year' => 2020, 'color' => 'Blanco', 'department' => 'Huehuetenango', 'municipality' => 'Chiantla', 'zone' => 'Centro', 'theft_latitude' => 15.3833, 'theft_longitude' => -91.4500, 'theft_address' => 'Carretera a los Cuchumatanes, Chiantla', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Robo en zona montañosa, difícil acceso.'],
            ['vin' => '1HGCM82633A000027', 'plate' => 'H300CCC', 'brand' => 'Nissan', 'model' => 'Frontier', 'year' => 2019, 'color' => 'Rojo', 'department' => 'Huehuetenango', 'municipality' => 'Huehuetenango', 'zone' => 'Zona 2', 'theft_latitude' => 15.3200, 'theft_longitude' => -91.4700, 'theft_address' => 'Boulevard, Huehuetenango', 'status' => 'activo', 'theft_status' => null, 'theft_desc' => null],
            
            // Jalapa
            ['vin' => '1HGCM82633A000028', 'plate' => 'J100AAA', 'brand' => 'Toyota', 'model' => 'Fortuner', 'year' => 2022, 'color' => 'Negro', 'department' => 'Jalapa', 'municipality' => 'Jalapa', 'zone' => 'Zona 1', 'theft_latitude' => 14.6333, 'theft_longitude' => -89.9833, 'theft_address' => 'Parque Central, Jalapa', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Asalto en carretera, zona rural.'],
            ['vin' => '1HGCM82633A000029', 'plate' => 'J200BBB', 'brand' => 'Chevrolet', 'model' => 'Colorado', 'year' => 2021, 'color' => 'Gris', 'department' => 'Jalapa', 'municipality' => 'San Pedro Pinula', 'zone' => 'Centro', 'theft_latitude' => 14.6167, 'theft_longitude' => -90.0167, 'theft_address' => 'Carretera a Jalapa, San Pedro Pinula', 'status' => 'activo', 'theft_status' => null, 'theft_desc' => null],
            
            // Jutiapa
            ['vin' => '1HGCM82633A000030', 'plate' => 'U100AAA', 'brand' => 'Mitsubishi', 'model' => 'Outlander', 'year' => 2020, 'color' => 'Blanco', 'department' => 'Jutiapa', 'municipality' => 'Jutiapa', 'zone' => 'Zona 1', 'theft_latitude' => 14.2833, 'theft_longitude' => -89.9000, 'theft_address' => 'Centro de Jutiapa', 'status' => 'reportado', 'theft_status' => 'activo', 'theft_desc' => 'Robado en estacionamiento público.'],
        ];

        foreach ($vehiclesData as $data) {
            $theftAddress = $data['theft_address'] ?? null;
            $theftLat = $data['theft_latitude'] ?? null;
            $theftLng = $data['theft_longitude'] ?? null;
            $status = $data['status'];
            $theftStatus = $data['theft_status'] ?? null;
            $theftDesc = $data['theft_desc'] ?? null;

            $vehicle = Vehicle::create([
                'vin' => $data['vin'],
                'plate' => $data['plate'],
                'brand' => $data['brand'],
                'model' => $data['model'],
                'year' => $data['year'],
                'color' => $data['color'],
                'department' => $data['department'],
                'municipality' => $data['municipality'],
                'zone' => $data['zone'],
                'theft_report_address' => $theftAddress,
                'theft_latitude' => $theftLat,
                'theft_longitude' => $theftLng,
                'status' => $status,
                'observations' => 'Vehículo de prueba - Seeder académico',
                'registered_by' => $admin->id,
            ]);

            // Si tiene reporte de robo, crearlo
            if ($theftStatus) {
                TheftReport::create([
                    'vehicle_id' => $vehicle->id,
                    'report_date' => now()->subDays(rand(1, 60))->format('Y-m-d'),
                    'description' => $theftDesc,
                    'status' => $theftStatus,
                    'reported_by' => $admin->id,
                ]);
            }
        }

        $this->command->info('✅ 30 vehículos ficticios creados exitosamente con distribución geográfica en Guatemala.');
    }
}