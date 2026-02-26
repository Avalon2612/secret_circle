<?php
/**
 * SecretCircle - PHP Ephemeral Chat
 * This version uses simple file-based polling for basic PHP hosting.
 */

$roomId = $_GET['room'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecretCircle - Anonymous Ephemeral Chat</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div id="app">
        <!-- Landing View -->
        <?php if (!$roomId): ?>
        <div class="view landing">
            <div class="accent top-left"></div>
            <div class="accent bottom-right"></div>
            
            <div class="content">
                <div class="icon-box">
                    <i data-lucide="ghost"></i>
                </div>
                <h1>SECRET<br>CIRCLE</h1>
                <p>Ephemeral, anonymous, and completely private.<br>Create a room, share the link, and chat. No logs. No traces.</p>
                
                <button id="start-btn" class="btn-primary">
                    <i data-lucide="sparkles"></i>
                    Start Chat Session
                </button>

                <div class="features">
                    <div class="feature">
                        <i data-lucide="zap"></i>
                        <h3>Instant</h3>
                        <p>No signup. Just one click.</p>
                    </div>
                    <div class="feature">
                        <i data-lucide="shield-alert"></i>
                        <h3>Private</h3>
                        <p>Messages are temporary.</p>
                    </div>
                    <div class="feature">
                        <i data-lucide="users"></i>
                        <h3>Group</h3>
                        <p>Share with up to 4 friends.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Setup View -->
        <div id="setup-view" class="view setup">
            <div class="card">
                <h2>Your Secret Identity</h2>
                <div class="form-group">
                    <label>Nickname</label>
                    <input type="text" id="nickname" placeholder="Who are you tonight?" maxlength="20">
                </div>
                <div class="form-group">
                    <label>Avatar Color</label>
                    <div class="color-grid" id="color-grid"></div>
                </div>
                <button id="enter-btn" class="btn-full">Enter the Circle</button>
            </div>
        </div>

        <!-- Chat View -->
        <div id="chat-view" class="view chat hidden">
            <div class="sidebar">
                <div class="sidebar-header">
                    <div class="logo">
                        <i data-lucide="ghost"></i>
                        <span>SECRET CIRCLE</span>
                    </div>
                    <div class="share-box">
                        <p>Share this room</p>
                        <div class="share-input">
                            <span id="room-url"></span>
                            <button id="copy-btn"><i data-lucide="copy"></i></button>
                        </div>
                    </div>
                </div>
                <div class="members-list">
                    <div class="list-header">
                        <h3>Active Members</h3>
                    </div>
                    <div id="users-container" class="users-container"></div>
                </div>
                <div class="sidebar-footer">
                    <i data-lucide="shield-alert"></i>
                    <p>Everything is ephemeral. Closing this tab destroys all session data.</p>
                </div>
            </div>

            <div class="main-chat">
                <div class="mobile-header">
                    <div class="logo">
                        <i data-lucide="ghost"></i>
                        <span>SECRET CIRCLE</span>
                    </div>
                    <button id="mobile-share"><i data-lucide="share-2"></i></button>
                </div>

                <div id="messages-container" class="messages-container"></div>

                <div class="input-area">
                    <form id="chat-form">
                        <div class="input-wrapper">
                            <input type="text" id="chat-input" placeholder="Whisper something secret..." autocomplete="off">
                            <div class="input-actions">
                                <button type="button" id="attach-btn"><i data-lucide="paperclip"></i></button>
                                <button type="submit" id="send-btn"><i data-lucide="send"></i></button>
                            </div>
                        </div>
                        <input type="file" id="file-input" hidden>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const COLORS = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7D794', '#786FA6', '#F8A5C2', '#63CDFF', '#546DE5'];
        const NICKNAMES = ['Neon Fox', 'Cyber Panda', 'Glitch Cat', 'Pixel Owl', 'Quantum Bear', 'Static Wolf', 'Binary Rabbit', 'Void Dragon'];
        
        let currentNickname = '';
        let currentColor = COLORS[0];
        let roomId = '<?php echo $roomId; ?>';
        let lastTimestamp = 0;

        // Initialize Lucide icons
        lucide.createIcons();

        // Landing logic
        const startBtn = document.getElementById('start-btn');
        if (startBtn) {
            startBtn.onclick = () => {
                const id = Math.random().toString(36).substring(2, 12);
                window.location.href = '?room=' + id;
            };
        }

        // Setup logic
        if (roomId) {
            const colorGrid = document.getElementById('color-grid');
            const nickInput = document.getElementById('nickname');
            const enterBtn = document.getElementById('enter-btn');
            const roomUrlSpan = document.getElementById('room-url');

            if (roomUrlSpan) roomUrlSpan.innerText = window.location.href;

            nickInput.value = NICKNAMES[Math.floor(Math.random() * NICKNAMES.length)];
            
            COLORS.forEach(c => {
                const btn = document.createElement('button');
                btn.className = 'color-btn';
                btn.style.backgroundColor = c;
                if (c === currentColor) btn.classList.add('active');
                btn.onclick = () => {
                    document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentColor = c;
                };
                colorGrid.appendChild(btn);
            });

            enterBtn.onclick = () => {
                currentNickname = nickInput.value.trim();
                if (!currentNickname) return;
                
                document.getElementById('setup-view').classList.add('hidden');
                document.getElementById('chat-view').classList.remove('hidden');
                startPolling();
            };
        }

        // Chat logic
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const messagesContainer = document.getElementById('messages-container');
        const fileInput = document.getElementById('file-input');
        const attachBtn = document.getElementById('attach-btn');

        if (chatForm) {
            chatForm.onsubmit = (e) => {
                e.preventDefault();
                const text = chatInput.value.trim();
                if (!text) return;
                sendMessage({ type: 'chat', text });
                chatInput.value = '';
            };

            attachBtn.onclick = () => fileInput.click();
            fileInput.onchange = (e) => {
                const file = e.target.files[0];
                if (!file) return;
                if (file.size > 2 * 1024 * 1024) {
                    alert("File too large (max 2MB for PHP version)");
                    return;
                }
                const reader = new FileReader();
                reader.onload = () => {
                    sendMessage({
                        type: 'file',
                        fileData: {
                            name: file.name,
                            type: file.type,
                            data: reader.result,
                            size: file.size
                        }
                    });
                };
                reader.readAsDataURL(file);
            };
        }

        async function sendMessage(payload) {
            await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    roomId,
                    nickname: currentNickname,
                    color: currentColor,
                    ...payload
                })
            });
        }

        function startPolling() {
            setInterval(async () => {
                const res = await fetch(`api.php?room=${roomId}&since=${lastTimestamp}`);
                const data = await res.json();
                
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        renderMessage(msg);
                        lastTimestamp = Math.max(lastTimestamp, msg.timestamp);
                    });
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }

                const usersContainer = document.getElementById('users-container');
                if (data.users) {
                    usersContainer.innerHTML = data.users.map(u => `
                        <div class="user-item">
                            <div class="user-avatar" style="background-color: ${u.color}"></div>
                            <span>${u.nickname} ${u.nickname === currentNickname ? '(You)' : ''}</span>
                        </div>
                    `).join('');
                }
            }, 2000);
        }

        function renderMessage(msg) {
            const div = document.createElement('div');
            div.className = `message-wrapper ${msg.nickname === currentNickname ? 'own' : ''}`;
            
            let content = '';
            if (msg.type === 'chat') {
                content = `<div class="bubble">${msg.text}</div>`;
            } else if (msg.type === 'file') {
                if (msg.fileData.type.startsWith('image/')) {
                    content = `<div class="bubble file"><img src="${msg.fileData.data}"><p>${msg.fileData.name}</p></div>`;
                } else {
                    content = `<div class="bubble file"><i data-lucide="file-text"></i><span>${msg.fileData.name}</span></div>`;
                }
            }

            div.innerHTML = `
                <div class="avatar" style="background-color: ${msg.color}"></div>
                <div class="msg-content">
                    <div class="meta"><span>${msg.nickname}</span></div>
                    ${content}
                </div>
            `;
            messagesContainer.appendChild(div);
            lucide.createIcons();
        }

        // Copy Link
        const copyBtn = document.getElementById('copy-btn');
        if (copyBtn) {
            copyBtn.onclick = () => {
                navigator.clipboard.writeText(window.location.href);
                copyBtn.innerHTML = '<i data-lucide="check"></i>';
                lucide.createIcons();
                setTimeout(() => {
                    copyBtn.innerHTML = '<i data-lucide="copy"></i>';
                    lucide.createIcons();
                }, 2000);
            };
        }
    </script>
</body>
</html>
