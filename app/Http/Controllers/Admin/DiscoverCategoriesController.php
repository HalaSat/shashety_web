<?php

namespace App\Http\Controllers\Admin;

use App\Categorie;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DiscoverCategory;
use Illuminate\Support\Facades\Storage;

class DiscoverCategoriesController extends Controller
{

    /**
     * Get all categories by KIND
     *
     * @param $kind
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllCategories()
    {
        $getCategories = DiscoverCategory::get();



        return response()->json(
            [
                'status' => 'success',
                'data' => [
                    'categories' => $getCategories,
                ]
            ],
            200
        );
    }


    /**
     * Create new category
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createCategory(Request $request)
    {
        $cat = Categorie::find($request->category_id);

        if ($cat) {
            $category = new DiscoverCategory();
            $category->categorie_id = $request->category_id;
            $category->name = $cat->name;
            $category->kind = $cat->kind;
            $category->save();
        } else {
            return response()->json(['status' => 'failed', 'message' => 'No category found'], 404);
        }


        return response()->json(['status' => 'success', 'message' => 'Added category to dicover categories'], 200);
    }



    /**
     * Delete category
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     *
     */

    public function deleteCategory($id)
    {
        $delete = DiscoverCategory::find($id)->delete();

        if ($delete) {
            return response()->json(['status' => 'success', 'message' => 'Successfully deleted'], 200);
        }

        return response()->json(['status' => 'failed', 'message' => 'Failed to delete'], 422);
    }
}
