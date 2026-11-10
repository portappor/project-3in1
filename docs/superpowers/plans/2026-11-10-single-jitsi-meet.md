# Single Pure Jitsi Meet Video Conference Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor the virtual classroom to use exclusively one pure Jitsi Meet container (Option A) on `meet.jit.si`, removing the dual-engine WebRTC switcher while providing role-based host/student guidance and fully integrated toolbar controls.

**Architecture:** Remove native WebRTC local streams and switcher UI from `resources/views/classroom/room.blade.php`. Automatically initialize `JitsiMeetExternalAPI` on `DOMContentLoaded`. Wire bottom toolbar buttons (Mic, Camera, Share Screen, Mute All, Raise Hand) directly to Jitsi commands and state-change listeners. Preserve multi-mode views (Presentation, Split 50:50, Tatap Muka) without disconnecting the meeting.

**Tech Stack:** Laravel 13, Blade, Tailwind CSS, JavaScript (ES6+), Jitsi Meet External API SDK (`https://meet.jit.si/external_api.js`), PHPUnit 12.

## Global Constraints
- Target file: `resources/views/classroom/room.blade.php` and `tests/Feature/VirtualClassroomAuthRbacTest.php`
- Preserve all existing routes, controllers, and database schema untouched
- Retain all existing assertions in `VirtualClassroomAuthRbacTest`
- Format all modified PHP code with `vendor/bin/pint --format agent`

---

### Task 1: Update Test Suite with Single Jitsi Expectations

**Files:**
- Modify: `tests/Feature/VirtualClassroomAuthRbacTest.php:215-240`

**Interfaces:**
- Consumes: Existing test structure in `tests/Feature/VirtualClassroomAuthRbacTest.php`
- Produces: Updated `test_both_dosen_and_mahasiswa_can_access_virtual_classroom_room` asserting single Jitsi status, role-specific guidance, and absence of dual-engine switcher elements.

- [ ] **Step 1: Write the updated test assertions**

In `tests/Feature/VirtualClassroomAuthRbacTest.php`, update lines 215-240:
```php
        $dosenRoom->assertSee('Host Utama (Dosen)');
        $dosenRoom->assertSee('Mute All Peserta');
        $dosenRoom->assertSee('Share Screen');
        $dosenRoom->assertSee('drag-splitter');
        $dosenRoom->assertSee('classroom-split-container');
        $dosenRoom->assertSee('external_api.js');
        $dosenRoom->assertSee('jitsi-meet-container');
        $dosenRoom->assertSee('Tatap Muka Jitsi Meet');
        $dosenRoom->assertSee('permintaan moderator');
        $dosenRoom->assertDontSee('btn-engine-webrtc');
        $dosenRoom->assertDontSee('btn-engine-jitsi');
        $dosenRoom->assertSee('btn-mode-presentation');
        $dosenRoom->assertSee('btn-mode-split');
        $dosenRoom->assertSee('btn-mode-video');
        $dosenRoom->assertSee('id="mic-btn"', false);
        $dosenRoom->assertSee('id="cam-btn"', false);
        $dosenRoom->assertSee('id="share-screen-btn"', false);

        $mhsRoom = $this->actingAs($this->mahasiswa)->get('/room/3IN1-TEST');
        $mhsRoom->assertStatus(200);
        $mhsRoom->assertSee('Peserta (Mahasiswa)');
        $mhsRoom->assertSee('Raise Hand (Angkat Tangan)');
        $mhsRoom->assertSee('Share Screen');
        $mhsRoom->assertSee('Sync Lock');
        $mhsRoom->assertSee('drag-splitter');
        $mhsRoom->assertSee('external_api.js');
        $mhsRoom->assertSee('jitsi-meet-container');
        $mhsRoom->assertSee('Tatap Muka Jitsi Meet');
        $mhsRoom->assertSee('Otomatis bergabung begitu Host membuka ruangan');
        $mhsRoom->assertDontSee('btn-engine-webrtc');
        $mhsRoom->assertDontSee('btn-engine-jitsi');
        $mhsRoom->assertSee('id="mic-btn"', false);
        $mhsRoom->assertSee('id="cam-btn"', false);
```

- [ ] **Step 2: Run test to verify it fails as expected**

Run: `php artisan test tests/Feature/VirtualClassroomAuthRbacTest.php --filter=test_both_dosen_and_mahasiswa_can_access_virtual_classroom_room` with `BypassSandbox: true`
Expected: FAIL because `room.blade.php` still contains `btn-engine-webrtc` and lacks the new banner copy.

- [ ] **Step 3: Commit updated test**

```bash
git add tests/Feature/VirtualClassroomAuthRbacTest.php
git commit -m "test: add assertions for single jitsi meet layout and role guidance"
```

---

### Task 2: Refactor HTML Structure in `room.blade.php`

**Files:**
- Modify: `resources/views/classroom/room.blade.php:234-332`

**Interfaces:**
- Consumes: `$isHost`, `$classroom`, `$user` passed from `VirtualClassroomController`
- Produces: Clean single-engine video stage `#jitsi-video-section` containing only `#jitsi-meet-container` and informative header/status banners.

- [ ] **Step 1: Replace `#jitsi-video-section` HTML**

Replace lines 234-332 in `resources/views/classroom/room.blade.php` with:
```blade
                <!-- Video Conference Section (Single Pure Jitsi Meet Engine) -->
                <div id="jitsi-video-section" class="w-full lg:w-1/2 h-full flex flex-col relative rounded-2xl overflow-hidden border border-slate-700 bg-slate-950 transition-all duration-300">
                    
                    <!-- Video Stage Header Bar -->
                    <div class="z-20 bg-slate-900/95 backdrop-blur px-3 py-1.5 border-b border-slate-800 flex flex-wrap items-center justify-between text-xs gap-2">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span id="video-engine-status" class="font-bold text-slate-100 text-[11px] tracking-wide flex items-center space-x-1.5">
                                <i class="fa-solid fa-video text-purple-400"></i>
                                <span>Tatap Muka Jitsi Meet</span>
                            </span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">meet.jit.si</span>
                        </div>
                        
                        <!-- Role-based Indicator Pill -->
                        <div class="flex items-center space-x-1.5 text-[10px]">
                            @if($isHost)
                                <span class="px-2 py-0.5 rounded-md bg-purple-950/80 text-purple-300 border border-purple-700/80 font-medium flex items-center space-x-1" title="Anda bertindak sebagai Host Ruang Kelas">
                                    <i class="fa-solid fa-crown text-amber-400 text-[9px]"></i>
                                    <span>Host Dosen</span>
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md bg-emerald-950/80 text-emerald-300 border border-emerald-700/80 font-medium flex items-center space-x-1">
                                    <i class="fa-solid fa-user-graduate text-emerald-400 text-[9px]"></i>
                                    <span>Peserta Mahasiswa</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Role-based Operational Notice Banner -->
                    @if($isHost)
                        <div id="jitsi-host-notice" class="z-15 bg-indigo-950/90 border-b border-indigo-800/80 px-3 py-1 text-indigo-200 text-[10px] flex items-center justify-between">
                            <span class="flex items-center space-x-1.5">
                                <i class="fa-solid fa-lightbulb text-amber-300"></i>
                                <span>Host: Jika video meminta izin, klik tombol <strong>"Masuk"</strong> untuk membuka ruangan bagi seluruh mahasiswa.</span>
                            </span>
                            <span class="text-[9px] text-indigo-300 font-mono">1x Login Google</span>
                        </div>
                    @else
                        <div id="jitsi-student-notice" class="z-15 bg-slate-900/90 border-b border-slate-800 px-3 py-1 text-slate-300 text-[10px] flex items-center space-x-1.5">
                            <i class="fa-solid fa-circle-check text-emerald-400"></i>
                            <span>Otomatis bergabung begitu Host membuka ruangan (tanpa perlu login).</span>
                        </div>
                    @endif

                    <!-- Sole Video Container: Jitsi Meet Iframe Mount Point -->
                    <div id="jitsi-stage" class="w-full flex-1 relative flex flex-col bg-slate-950 overflow-hidden">
                        <div id="jitsi-meet-container" class="w-full flex-1 h-full"></div>

                        <!-- Jitsi Loading Spinner -->
                        <div id="jitsi-loader" class="pointer-events-none absolute inset-0 bg-slate-900/90 flex flex-col items-center justify-center text-white space-y-3 z-10 transition-opacity duration-500">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center animate-pulse shadow-lg">
                                <i class="fa-solid fa-video text-xl"></i>
                            </div>
                            <p class="text-xs font-semibold tracking-wide">Menghubungkan ke Video Conference Jitsi Meet...</p>
                            <span class="text-[11px] text-slate-400 font-mono">{{ config('services.jitsi.domain', 'meet.jit.si') }}</span>
                        </div>
                    </div>

                </div>
```

- [ ] **Step 2: Check Blade file syntax**

Run: `php -l resources/views/classroom/room.blade.php`
Expected: No syntax errors detected.

---

### Task 3: Refactor JavaScript Logic in `room.blade.php`

**Files:**
- Modify: `resources/views/classroom/room.blade.php:800-1350`

**Interfaces:**
- Consumes: Jitsi Meet External API (`JitsiMeetExternalAPI`) loaded from `https://meet.jit.si/external_api.js`
- Produces: Streamlined JavaScript initialization and toolbar event handlers without WebRTC fallback complexity.

- [ ] **Step 1: Update `DOMContentLoaded` to auto-init Jitsi**

In `DOMContentLoaded`, replace `initWebRTC()` with:
```javascript
        renderPresentationView();

        // Initialize Pure Jitsi Meet Video Engine automatically
        initJitsiMeet();

        // Start Periodic Synchronization Loop
        setInterval(pollRoomState, 2500);
        pollRoomState();
```

- [ ] **Step 2: Streamline `initJitsiMeet()` and clean up WebRTC helper code**

Replace `initWebRTC`, `switchVideoEngine`, and `showCameraOffPlaceholder` with clean Jitsi-exclusive methods:
- `initJitsiMeet()` initializes `JitsiMeetExternalAPI` with room `unuja-3in1-{{ Str::slug($classroom->room_code ?? $room_code) }}`.
- Remove unused WebRTC variables: `localStream`, `screenStream`.
- In `setStageMode(mode)`:
  - Keep `jitsiSection` hidden off-screen (`absolute -left-[9999px] w-1 h-1 overflow-hidden opacity-0 pointer-events-none`) when mode is `presentation`.
  - In `split` or `video`, bring `jitsiSection` into view without re-initializing or refreshing the iframe.
- In `toggleMyMic()`:
  - Trigger `jitsiApi.executeCommand('toggleAudio')`.
  - Update local state & sync to backend route `/classroom/{room_code}/mic`.
- In `toggleCam()`:
  - Trigger `jitsiApi.executeCommand('toggleVideo')`.
  - Update cam button UI.
- In `toggleShareScreen()`:
  - Trigger `jitsiApi.executeCommand('toggleShareScreen')`.
- Ensure Jitsi event listeners handle UI sync:
  ```javascript
  jitsiApi.addEventListener('audioMuteStatusChanged', (e) => {
      isMyMicActive = !e.muted;
      updateMicButtonUI(!e.muted);
  });
  jitsiApi.addEventListener('videoMuteStatusChanged', (e) => {
      isCamActive = !e.muted;
      updateCamButtonUI(!e.muted);
  });
  jitsiApi.addEventListener('screenSharingStatusChanged', (e) => {
      isScreenSharing = e.on;
      updateShareScreenUI(e.on);
  });
  ```

- [ ] **Step 3: Run PHP syntax validation**

Run: `php -l resources/views/classroom/room.blade.php`
Expected: No syntax errors detected.

---

### Task 4: Verification, Formatting, and Git Commit

**Files:**
- Modify: `resources/views/classroom/room.blade.php`
- Modify: `tests/Feature/VirtualClassroomAuthRbacTest.php`

**Interfaces:**
- Consumes: PHPUnit test runner, Laravel Pint
- Produces: Clean, passing test suite and git commit on `main`.

- [ ] **Step 1: Run feature test suite**

Run: `php artisan test tests/Feature/VirtualClassroomAuthRbacTest.php` with `BypassSandbox: true`
Expected: 20 passed, all assertions green (100% PASS).

- [ ] **Step 2: Run full application test suite**

Run: `php artisan test` with `BypassSandbox: true`
Expected: All tests pass.

- [ ] **Step 3: Run Laravel Pint code formatter**

Run: `vendor/bin/pint --format agent`
Expected: Code formatted to Laravel standards.

- [ ] **Step 4: Commit changes to Git**

```bash
git add resources/views/classroom/room.blade.php tests/Feature/VirtualClassroomAuthRbacTest.php
git commit -m "feat: simplify virtual classroom to single pure jitsi meet engine"
```

