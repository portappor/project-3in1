# Design Document: Single Pure Jitsi Meet Video Conference (Option A)

## Context & Problem Statement
Previously, `resources/views/classroom/room.blade.php` featured a dual-engine video conferencing setup with a manual switcher between "WebRTC Lokal" and "Jitsi Meet (External API)". The public instance `meet.jit.si` prompts for a moderator login (*"The conference has not yet started because no moderators have yet arrived. If you'd like to become a moderator please log-in. Otherwise, please wait."*), while alternative public community instances like `meet.ffmuc.net` actively reject iframe embedding via `X-Frame-Options: SAMEORIGIN` / CSP policies.

The user selected **Option A**:
- Streamline the virtual classroom interface to use **exclusively ONE pure Jitsi Meet container** (removing the WebRTC switcher buttons and local WebRTC video placeholder).
- Use the official and iframe-permissive `meet.jit.si` server.
- The lecturer/dosen (Host) logs in once via Google/GitHub to claim moderator rights and unlock the room.
- All students (participants) join automatically into the unlocked room without requiring any login credentials.

---

## Architecture & Component Design

### 1. Blade Template Refactoring (`resources/views/classroom/room.blade.php`)
- **Video Section Header (`#jitsi-video-section`)**:
  - Replace the dual-engine toggle buttons (`#btn-engine-webrtc` and `#btn-engine-jitsi`) with a unified status bar:
    - Left: Indicator icon and label `Tatap Muka Jitsi Meet (meet.jit.si)`.
    - Right / Sub-bar:
      - If `$isHost`: A moderator helper banner:
        *"Host Dosen: Klik 'Masuk' pada video jika muncul permintaan moderator untuk membuka sesi kelas."*
      - If not `$isHost`: An attendee helper badge:
        *"Peserta: Otomatis bergabung begitu Host membuka ruangan."*
- **Stage Container**:
  - Remove `#webrtc-stage` and all local camera video tags (`#local-camera-feed`), camera-off avatar placeholders, and native screen share overlay.
  - Position `#jitsi-meet-container` as the sole, full-dimension child (`w-full h-full flex-1`) inside `#jitsi-video-section`.
  - Preserve the `#jitsi-loader` overlay with retry capability in case of network latency.

### 2. JavaScript Lifecycle & Event Integration
- **Auto-Initialization**:
  - In `DOMContentLoaded`, trigger `initJitsiMeet()` immediately instead of initializing local WebRTC hardware.
  - Set `roomName` cleanly as `unuja-3in1-[room_code]`.
  - Set `userInfo.displayName` to `{{ $user->name }} ({{ $isHost ? 'Dosen' : 'Mahasiswa' }})`.
  - Provide optimal `configOverwrite`:
    - `disableDeepLinking: true`
    - `prejoinPageEnabled: false`
    - `enableWelcomePage: false`
    - `enableClosePage: false`
    - Toolbar buttons enabled: microphone, camera, desktop, chat, raisehand, videoquality, tileview, settings, mute-everyone.
- **Unified Toolbar Controls**:
  - `toggleMyMic()`: Calls `jitsiApi.executeCommand('toggleAudio')` and syncs microphone status with backend route `/classroom/{room_code}/mic`.
  - `toggleCam()`: Calls `jitsiApi.executeCommand('toggleVideo')`.
  - `toggleShareScreen()`: Calls `jitsiApi.executeCommand('toggleShareScreen')`.
  - `toggleMyRaiseHand()`: Calls `jitsiApi.executeCommand('toggleRaiseHand')` and syncs with backend route `/classroom/{room_code}/raise-hand`.
  - `toggleMuteAll()` (Host only): Calls `jitsiApi.executeCommand('muteEveryone')` and syncs with backend route `/dosen/classroom/{room_code}/mute-all`.
  - Two-way status listeners on `jitsiApi`:
    - `audioMuteStatusChanged` -> `updateMicButtonUI(!e.muted)`
    - `videoMuteStatusChanged` -> `updateCamButtonUI(!e.muted)`
    - `screenSharingStatusChanged` -> `updateShareScreenUI(e.on)`
- **Stage View Modes**:
  - Mode `presentation`: Slide deck takes 100% width. Jitsi container remains active in off-screen DOM (`absolute -left-[9999px] w-1 h-1 opacity-0 pointer-events-none`) so audio/video calls remain connected without interruption.
  - Mode `split`: Slide deck takes left 50%, Jitsi Meet takes right 50%.
  - Mode `video`: Jitsi Meet takes 100% width and height.

---

## Error Handling & Edge Cases
1. **Host Moderator Prompt**: Clear visual instruction is shown so the lecturer knows to click "Masuk" once to unlock the room.
2. **Student Arriving Early**: Student sees the native Jitsi waiting state, and once the lecturer enters, the student is seamlessly admitted without any login.
3. **Network Glitch / Jitsi CDN Failure**: The fallback loader `#jitsi-loader` shows a "Coba Hubungkan Lagi" retry button if the Jitsi script takes more than 8 attempts to load.
4. **Layout Resizing**: Splitting between presentation and video adjusts dynamically without iframe reload.

---

## Verification Plan
1. **Automated Tests**:
   - Run `php artisan test tests/Feature/VirtualClassroomAuthRbacTest.php` to ensure all 20 assertions for room access, controls, and DOM elements pass cleanly.
2. **Code Style**:
   - Run `vendor/bin/pint --format agent` to maintain strict code conventions.
3. **Blade Template Syntax**:
   - Verify proper closing tags and JavaScript syntax in `resources/views/classroom/room.blade.php`.
