<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class BusinessLocation extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'featured_products' => 'array',
        'attendance_geofence_enabled' => 'boolean',
        'attendance_geofence_polygon' => 'array',
    ];

    /**
     * Whether this location has a configured clock-in geofence (polygon or legacy circle).
     */
    public function hasActiveAttendanceGeofence(): bool
    {
        if (empty($this->attendance_geofence_enabled)) {
            return false;
        }
        $poly = $this->attendance_geofence_polygon;
        if (is_array($poly) && count($poly) >= 3) {
            return true;
        }
        if ($this->attendance_geofence_latitude === null || $this->attendance_geofence_longitude === null) {
            return false;
        }
        $r = (int) ($this->attendance_geofence_radius_meters ?? 0);

        return $r > 0;
    }

    /**
     * Ray-casting: point [lat, lng] inside polygon [[lat,lng], ...] (WGS-84, small area).
     */
    public static function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $n = count($polygon);
        if ($n < 3) {
            return false;
        }
        $inside = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = (float) $polygon[$i][0];
            $xi = (float) $polygon[$i][1];
            $yj = (float) $polygon[$j][0];
            $xj = (float) $polygon[$j][1];
            $den = $yj - $yi;
            if (abs($den) < 1e-12) {
                $den = $den >= 0 ? 1e-12 : -1e-12;
            }
            if ((($yi > $lat) != ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / $den + $xi)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Great-circle distance in meters (Haversine).
     */
    public static function distanceMetersBetweenCoordinates(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth * $c;
    }

    /**
     * True if the point is inside the configured polygon, or (legacy) inside the circle.
     */
    public function isCoordinateInsideAttendanceGeofence(float $latitude, float $longitude): bool
    {
        if (! $this->hasActiveAttendanceGeofence()) {
            return false;
        }
        $poly = $this->attendance_geofence_polygon;
        if (is_array($poly) && count($poly) >= 3) {
            return self::isPointInPolygon($latitude, $longitude, $poly);
        }

        $d = self::distanceMetersBetweenCoordinates(
            (float) $this->attendance_geofence_latitude,
            (float) $this->attendance_geofence_longitude,
            $latitude,
            $longitude
        );

        return $d <= (int) $this->attendance_geofence_radius_meters;
    }

    /**
     * Return list of locations for a business
     *
     * @param  int  $business_id
     * @param  bool  $show_all = false
     * @param  array  $receipt_printer_type_attribute =
     * @return array
     */
    public static function forDropdown($business_id, $show_all = false, $receipt_printer_type_attribute = false, $append_id = true, $check_permission = true)
    {
        $query = BusinessLocation::where('business_id', $business_id)->Active();

        if ($check_permission) {
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('id', $permitted_locations);
            }
        }

        if ($append_id) {
            $query->select(
                DB::raw("IF(location_id IS NULL OR location_id='', name, CONCAT(name, ' (', location_id, ')')) AS name"),
                'id',
                'receipt_printer_type',
                'selling_price_group_id',
                'default_payment_accounts',
                'invoice_scheme_id',
                'invoice_layout_id',
                'sale_invoice_scheme_id'
            );
        }

        $result = $query->get();

        $locations = $result->pluck('name', 'id');

        $price_groups = SellingPriceGroup::forDropdown($business_id);

        if ($show_all) {
            $locations->prepend(__('report.all_locations'), '');
        }

        if ($receipt_printer_type_attribute) {
            $attributes = collect($result)->mapWithKeys(function ($item) use ($price_groups) {
                $default_payment_accounts = json_decode($item->default_payment_accounts, true);
                $default_payment_accounts['advance'] = [
                    'is_enabled' => 1,
                    'account' => null,
                ];

                return [$item->id => [
                    'data-receipt_printer_type' => $item->receipt_printer_type,
                    'data-default_price_group' => ! empty($item->selling_price_group_id) && array_key_exists($item->selling_price_group_id, $price_groups) ? $item->selling_price_group_id : null,
                    'data-default_payment_accounts' => json_encode($default_payment_accounts),
                    'data-default_sale_invoice_scheme_id' => $item->sale_invoice_scheme_id,
                    'data-default_invoice_scheme_id' => $item->invoice_scheme_id,
                    'data-default_invoice_layout_id' => $item->invoice_layout_id,
                ],
                ];
            })->all();

            return ['locations' => $locations, 'attributes' => $attributes];
        } else {
            return $locations;
        }
    }

    public function price_group()
    {
        return $this->belongsTo(\App\SellingPriceGroup::class, 'selling_price_group_id');
    }

    /**
     * Scope a query to only include active location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Get the featured products.
     *
     * @return array/object
     */
    public function getFeaturedProducts($is_array = false, $check_location = true)
    {
        if (empty($this->featured_products)) {
            return [];
        }
        $query = Variation::whereIn('variations.id', $this->featured_products)
                                    ->join('product_locations as pl', 'pl.product_id', '=', 'variations.product_id')
                                    ->join('products as p', 'p.id', '=', 'variations.product_id')
                                    ->where('p.not_for_selling', 0)
                                    ->with(['product_variation', 'product', 'media'])
                                    ->select('variations.*');

        if ($check_location) {
            $query->where('pl.location_id', $this->id);
        }
        $featured_products = $query->get();
        if ($is_array) {
            $array = [];
            foreach ($featured_products as $featured_product) {
                $array[$featured_product->id] = $featured_product->full_name;
            }

            return $array;
        }

        return $featured_products;
    }

    public function getLocationAddressAttribute()
    {
        $location = $this;
        $address_line_1 = [];
        if (! empty($location->landmark)) {
            $address_line_1[] = $location->landmark;
        }
        if (! empty($location->city)) {
            $address_line_1[] = $location->city;
        }
        if (! empty($location->state)) {
            $address_line_1[] = $location->state;
        }
        if (! empty($location->zip_code)) {
            $address_line_1[] = $location->zip_code;
        }
        $address = implode(', ', $address_line_1);
        $address_line_2 = [];
        if (! empty($location->country)) {
            $address_line_2[] = $location->country;
        }
        $address .= '<br>';
        $address .= implode(', ', $address_line_2);

        return $address;
    }
}
