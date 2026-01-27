<?php
//
//use App\Models\User;
//use Illuminate\Http\UploadedFile;
//use Illuminate\Support\Facades\Storage;
//
//test('authenticated user can update their own profile', function () {
//    $user = User::factory()->create([
//        'name' => 'Old Name',
//        'email' => 'old@example.com',
//    ]);
//
//    $response = $this->actingAs($user, 'api')->patchJson('/api/me', [
//        'name' => 'New Name',
//        'email' => 'new@example.com',
//    ]);
//
//    $response->assertSuccessful()
//        ->assertJson([
//            'message' => 'Profile updated successfully',
//        ]);
//
//    $this->assertDatabaseHas('users', [
//        'id' => $user->id,
//        'name' => 'New Name',
//        'email' => 'new@example.com',
//    ]);
//});
//
////test('authenticated user can update their password', function () {
////    $user = User::factory()->create([
////        'password' => bcrypt('old_password'),
////    ]);
////
////    $response = $this->actingAs($user, 'api')->patchJson('/api/me', [
////        'password' => 'new_password123',
////    ]);
////
////    $response->assertSuccessful();
////
////    $user->refresh();
////    expect(\Illuminate\Support\Facades\Hash::check('new_password123', $user->password))->toBeTrue();
////});
//
//test('authenticated user cannot update their role via profile endpoint', function () {
//    $user = User::factory()->create(['role' => 'employee']);
//
//    $response = $this->actingAs($user, 'api')->patchJson('/api/me', [
//        'name' => 'Updated Name',
//        'role' => 'admin',
//    ]);
//
//    $response->assertSuccessful();
//
//    $user->refresh();
//    expect($user->role->value)->toBe('employee');
//});
//
//test('authenticated user can upload avatar', function () {
//    Storage::fake('public');
//
//    $user = User::factory()->create();
//    $file = UploadedFile::fake()->image('avatar.jpg');
//
//    $response = $this->actingAs($user, 'api')->postJson('/api/me/avatar', [
//        'avatar' => $file,
//    ]);
//
//    $response->assertSuccessful()
//        ->assertJson([
//            'message' => 'Avatar updated successfully',
//        ]);
//
//    $user->refresh();
//    expect($user->avatar)->not->toBeNull();
//    Storage::disk('public')->assertExists($user->avatar);
//});
//
//test('unauthenticated user cannot update profile', function () {
//    $response = $this->patchJson('/api/me', [
//        'name' => 'New Name',
//    ]);
//
//    $response->assertUnauthorized();
//});
//
//test('unauthenticated user cannot upload avatar', function () {
//    Storage::fake('public');
//    $file = UploadedFile::fake()->image('avatar.jpg');
//
//    $response = $this->postJson('/api/me/avatar', [
//        'avatar' => $file,
//    ]);
//
//    $response->assertUnauthorized();
//});
//
//test('admin can update any user via users endpoint with PUT', function () {
//    $admin = User::factory()->create(['role' => 'admin']);
//    $user = User::factory()->create(['name' => 'Old Name']);
//
//    $response = $this->actingAs($admin, 'api')->putJson("/api/users/{$user->id}", [
//        'name' => 'Admin Updated Name',
//    ]);
//
//    $response->assertSuccessful();
//
//    $this->assertDatabaseHas('users', [
//        'id' => $user->id,
//        'name' => 'Admin Updated Name',
//    ]);
//});
//
//test('admin can update any user via users endpoint with PATCH', function () {
//    $admin = User::factory()->create(['role' => 'admin']);
//    $user = User::factory()->create(['name' => 'Old Name']);
//
//    $response = $this->actingAs($admin, 'api')->patchJson("/api/users/{$user->id}", [
//        'name' => 'Admin Patched Name',
//    ]);
//
//    $response->assertSuccessful();
//
//    $this->assertDatabaseHas('users', [
//        'id' => $user->id,
//        'name' => 'Admin Patched Name',
//    ]);
//});
//
//test('admin can update user role via users endpoint', function () {
//    $admin = User::factory()->create(['role' => 'admin']);
//    $user = User::factory()->create(['role' => 'employee']);
//
//    $response = $this->actingAs($admin, 'api')->putJson("/api/users/{$user->id}", [
//        'role' => 'manager',
//    ]);
//
//    $response->assertSuccessful();
//
//    $this->assertDatabaseHas('users', [
//        'id' => $user->id,
//        'role' => 'manager',
//    ]);
//});
//
//test('non-admin cannot update another user', function () {
//    $user = User::factory()->create(['role' => 'employee']);
//    $anotherUser = User::factory()->create();
//
//    $response = $this->actingAs($user, 'api')->putJson("/api/users/{$anotherUser->id}", [
//        'name' => 'Hacked Name',
//    ]);
//
//    $response->assertForbidden();
//});
//
//test('profile update validates email uniqueness', function () {
//    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
//    $user = User::factory()->create(['email' => 'user@example.com']);
//
//    $response = $this->actingAs($user, 'api')->patchJson('/api/me', [
//        'email' => 'existing@example.com',
//    ]);
//
//    $response->assertStatus(422)
//        ->assertJsonValidationErrors(['email']);
//});
//
//test('user can update profile with their own email', function () {
//    $user = User::factory()->create([
//        'name' => 'John Doe',
//        'email' => 'john@example.com',
//    ]);
//
//    $response = $this->actingAs($user, 'api')->patchJson('/api/me', [
//        'name' => 'Jane Doe',
//        'email' => 'john@example.com', // Same email
//    ]);
//
//    $response->assertSuccessful()
//        ->assertJson([
//            'message' => 'Profile updated successfully',
//        ]);
//
//    $this->assertDatabaseHas('users', [
//        'id' => $user->id,
//        'name' => 'Jane Doe',
//        'email' => 'john@example.com',
//    ]);
//});
//
//test('profile update validates password minimum length', function () {
//    $user = User::factory()->create();
//
//    $response = $this->actingAs($user, 'api')->patchJson('/api/me', [
//        'password' => 'short',
//    ]);
//
//    $response->assertStatus(422)
//        ->assertJsonValidationErrors(['password']);
//});
