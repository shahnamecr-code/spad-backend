<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give the search panel's primary property chips real, editable advertisement
 * values. Reuse live property slugs wherever the existing type is compatible.
 *
 * Location is already persisted on advertisements (city/region/address); price
 * is already a category-specific listing property. Neither is duplicated.
 *
 * Only existing category-specific advertisement layouts are extended. Existing
 * layouts, configured filter groups and advertisements are never replaced.
 */
class SeedSpadPrimaryPropertyFields extends Migration
{
    private function normalized($value)
    {
        $value = str_replace(['ي', 'ك'], ['ی', 'ک'], (string) $value);
        return preg_replace('/[\s\x{200c}\x{200d}_-]+/u', '', $value);
    }

    private function collectLayoutSlugs($items, &$found)
    {
        foreach ((array) $items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['property_id']) && is_string($item['property_id'])) {
                $found[$item['property_id']] = true;
            }
            foreach ((array) ($item['dependency'] ?? []) as $dependency) {
                if (is_array($dependency) && isset($dependency['items'])) {
                    $this->collectLayoutSlugs($dependency['items'], $found);
                }
            }
        }
    }

    public function up()
    {
        foreach (['properties', 'categories', 'category_layouts', 'advertisement_properties'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new \RuntimeException('Missing required SPAD property table: ' . $table);
            }
        }
        foreach (['searchItems'] as $column) {
            if (!Schema::hasColumn('categories', $column)) {
                throw new \RuntimeException('Missing category filter configuration column: ' . $column);
            }
        }

        DB::transaction(function () {
            // Title aliases must match the existing physical type; never
            // silently repurpose a property that means something else.
            $definitions = [
                ['slug' => 'number_of_sleeps', 'aliases' => ['number_of_sleeps', 'bedrooms', 'bedroom_count'], 'titles' => ['تعداد خواب', 'تعداد اتاق خواب'], 'title' => 'تعداد خواب', 'type' => 'integer', 'allowed' => ['integer', 'select'], 'range' => false],
                ['slug' => 'spad_primary_area', 'aliases' => ['area', 'meterage', 'metrage', 'area_m2', 'spad_primary_area'], 'titles' => ['متراژ', 'زیربنا', 'مساحت بنا'], 'title' => 'متراژ', 'type' => 'integer', 'allowed' => ['integer'], 'range' => true],
                ['slug' => 'spad_primary_build_year', 'aliases' => ['age', 'year_built', 'build_year', 'construction_year', 'year_of_construction', 'spad_primary_build_year'], 'titles' => ['سال ساخت', 'سال بنا'], 'title' => 'سال ساخت', 'type' => 'integer', 'allowed' => ['integer', 'select', 'radio'], 'range' => true],
                ['slug' => 'spad_primary_floor', 'aliases' => ['floor', 'floor_number', 'unit_floor', 'storey', 'spad_primary_floor'], 'titles' => ['طبقه', 'طبقه واحد'], 'title' => 'طبقه', 'type' => 'integer', 'allowed' => ['integer', 'select', 'radio'], 'range' => true],
                ['slug' => 'spad_primary_sunlight', 'aliases' => ['sunlight', 'natural_light', 'light_exposure', 'spad_primary_sunlight'], 'titles' => ['نورگیر واحد', 'نورگیر', 'نورگیری'], 'title' => 'نورگیر واحد', 'type' => 'select', 'allowed' => ['select', 'radio', 'text'], 'range' => false, 'enum' => ['کم', 'متوسط', 'خوب', 'عالی']],
                ['slug' => 'spad_primary_parking', 'aliases' => ['parking', 'parking_count', 'has_parking', 'spad_primary_parking'], 'titles' => ['پارکینگ', 'تعداد پارکینگ'], 'title' => 'پارکینگ', 'type' => 'boolean', 'allowed' => ['boolean', 'integer', 'select', 'radio'], 'range' => false],
                ['slug' => 'spad_primary_elevator', 'aliases' => ['elevator', 'lift', 'has_elevator', 'spad_primary_elevator'], 'titles' => ['آسانسور'], 'title' => 'آسانسور', 'type' => 'boolean', 'allowed' => ['boolean', 'select', 'radio'], 'range' => false],
                ['slug' => 'spad_primary_storage', 'aliases' => ['warehouse', 'storage', 'storage_room', 'has_storage', 'spad_primary_storage'], 'titles' => ['انباری'], 'title' => 'انباری', 'type' => 'boolean', 'allowed' => ['boolean', 'select', 'radio'], 'range' => false],
            ];

            $existing = DB::table('properties')->select('slug', 'title', 'type')->get();
            $properties = [];
            foreach ($definitions as $position => $definition) {
                $compatible = $existing->filter(function ($property) use ($definition) {
                    return in_array($property->type, $definition['allowed'], true);
                });
                $match = $compatible->first(function ($property) use ($definition) {
                    return in_array($property->slug, $definition['aliases'], true);
                });
                if (!$match) {
                    $titles = array_map([$this, 'normalized'], $definition['titles']);
                    $match = $compatible->first(function ($property) use ($titles) {
                        return in_array($this->normalized($property->title), $titles, true);
                    });
                }
                if ($match) {
                    $slug = $match->slug;
                    $type = $match->type;
                } else {
                    $slug = $definition['slug'];
                    if ($existing->contains('slug', $slug)) {
                        throw new \RuntimeException('Incompatible existing SPAD property slug: ' . $slug);
                    }
                    $type = $definition['type'];
                    $enum = $definition['enum'] ?? null;
                    DB::table('properties')->insert([
                        'title' => $definition['title'],
                        'slug' => $slug,
                        'order' => 420 + $position,
                        'type' => $type,
                        'enum' => $enum ? json_encode($enum, JSON_UNESCAPED_UNICODE) : null,
                        'enumNames' => $enum ? json_encode($enum, JSON_UNESCAPED_UNICODE) : null,
                        'searchable' => 1,
                        'search_level' => 1,
                        'search_type' => $type === 'select' ? 'select' : 'text',
                        'search_operator' => 'eq',
                        'show_in_crm' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $existing->push((object) ['slug' => $slug, 'title' => $definition['title'], 'type' => $type]);
                }
                $properties[] = [
                    'slug' => $slug,
                    'type' => $type,
                    'range' => $type === 'integer' && $definition['range'],
                ];
            }

            $categoryIds = DB::table('category_layouts')->select('category_id')->distinct()
                ->pluck('category_id');
            foreach ($categoryIds as $categoryId) {
                $category = DB::table('categories')->where('id', $categoryId)->first();
                if (!$category) {
                    continue;
                }
                $layouts = DB::table('category_layouts')
                    ->where('category_id', $categoryId)->orderBy('order')->get();
                $present = [];
                $section = null;
                foreach ($layouts as $layoutRow) {
                    $layout = json_decode($layoutRow->layout ?: '[]', true);
                    if (!is_array($layout)) {
                        throw new \RuntimeException('Invalid layout JSON for category ' . $categoryId);
                    }
                    $this->collectLayoutSlugs($layout, $present);
                    if ($layoutRow->title === 'مشخصات اصلی ملک' && $layoutRow->position === 'normal') {
                        $section = $layoutRow;
                    }
                }

                $missing = [];
                foreach ($properties as $property) {
                    if (!isset($present[$property['slug']])) {
                        $missing[] = ['property_id' => $property['slug'], 'class' => 'col-12 col-md-6', 'access' => 0];
                    }
                }
                if ($missing) {
                    if ($section) {
                        $items = json_decode($section->layout ?: '[]', true);
                        if (!is_array($items)) {
                            throw new \RuntimeException('Invalid SPAD primary layout JSON');
                        }
                        DB::table('category_layouts')->where('id', $section->id)->update([
                            'layout' => json_encode(array_merge($items, $missing), JSON_UNESCAPED_UNICODE),
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('category_layouts')->insert([
                            'category_id' => $categoryId,
                            'title' => 'مشخصات اصلی ملک',
                            'position' => 'normal',
                            'access' => 0,
                            'order' => ((int) $layouts->max('order')) + 1,
                            'layout' => json_encode($missing, JSON_UNESCAPED_UNICODE),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $groups = json_decode($category->searchItems ?: '[]', true);
                if (!is_array($groups)) {
                    throw new \RuntimeException('Invalid SPAD category searchItems JSON');
                }
                $indexed = [];
                foreach ($groups as $group) {
                    foreach (($group['filters'] ?? []) as $filter) {
                        if (isset($filter['property_id'])) {
                            $indexed[$filter['property_id']] = true;
                        }
                    }
                }
                $filters = [];
                foreach ($properties as $property) {
                    if (isset($indexed[$property['slug']])) {
                        continue;
                    }
                    $filters[] = [
                        'property_id' => $property['slug'],
                        'search_type' => in_array($property['type'], ['select', 'radio', 'boolean'], true) ? 'select' : 'text',
                        'search_operator' => 'eq',
                        'range_search' => (bool) $property['range'],
                        'auto_complete' => false,
                    ];
                }
                if ($filters) {
                    $groups[] = [
                        'label' => 'مشخصات اصلی ملک',
                        'icon' => [],
                        'filters' => $filters,
                    ];
                    DB::table('categories')->where('id', $categoryId)->update([
                        'searchItems' => json_encode($groups, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down()
    {
        // Do not erase administrator-managed filters, layouts or ad values.
    }
}
