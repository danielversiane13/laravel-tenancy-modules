<?php

namespace Modules\Tenant\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Entities\Tenant;

class TenantController extends Controller
{
  public function index()
  {
    $tenants = Tenant::get();

    return response()->json($tenants);
  }

  public function store(Request $request)
  {
    $request->validate([
      'name' => ['required', 'string', 'unique:tenants,id'],
    ]);

    $tenant = Tenant::create([
      'id' => $request->name
    ]);

    return response()->json($tenant);
  }

  public function show($id)
  {
    $tenant = Tenant::find($id);
    throw_unless($tenant, new Exception('No tienes'));

    return response()->json($tenant);
  }

  public function update(Request $request, $id)
  {
    $request->validate([
      'name' => ['required', 'string', 'unique:tenants,id'],
    ]);

    $tenant = Tenant::find($id);
    throw_unless($tenant, new Exception('No tienes'));

    $tenant->update([
      'id' => $request->name,
    ]);

    return response()->json($tenant);
  }

  public function destroy($id)
  {
    $tenant = Tenant::find($id);
    throw_unless($tenant, new Exception('No tienes'));

    $tenant->delete();

    return response()->json(null, 204);
  }
}
