<?php require_once __DIR__ . '/app/bootstrap.php'; ?>
<?php include __DIR__ . '/app/views/partials/header.php'; ?>

<?php // Load featured data (and fallback to newest) for hero + grid ?>
<?php include __DIR__ . '/snippets/home_featured_query.php'; ?>

<!-- HERO -->
<section class="gradient-bg text-white py-16">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
    <div>
      <h2 class="text-5xl font-bold mb-6 leading-tight">
        Discover, Create & Trade <span class="text-yellow-300">Nevareth items</span>
      </h2>
      <p class="text-xl mb-8 opacity-90 leading-relaxed">
        Buy, sell, and create unique Nevareth items with ALZ currency.
      </p>
      <div class="flex flex-col sm:flex-row gap-4">
        <a href="<?= e(BASE_URL) ?>/marketplace.php"
           class="bg-white text-purple-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-gray-100 transition-all transform hover:scale-105">
          <i class="fas fa-rocket mr-2"></i>Explore
        </a>
        <a href="<?= e(BASE_URL) ?>/create.php"
           class="border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-purple-600 transition-all">
          <i class="fas fa-paint-brush mr-2"></i>Create
        </a>
      </div>
    </div>

    <!-- Two angled cards: now dynamic (featured-first, else newest) -->
    <div class="relative">
      <?php include __DIR__ . '/snippets/home_hero_cards.php'; ?>
    </div>
  </div>
</section>

<!-- Featured NFTs grid -->
<?php include __DIR__ . '/snippets/home_featured_grid.php'; ?>

<!-- Stats -->
<section class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
    <div>
      <div class="text-4xl font-bold text-purple-600 mb-2">
        <?php echo (int)db()->query('SELECT COUNT(*) c FROM nfts')->fetch_assoc()['c']; ?>
      </div>
      <div class="text-gray-600 font-medium">Total</div>
    </div>
    <div>
      <div class="text-4xl font-bold text-blue-600 mb-2">
        <?php echo (int)db()->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c']; ?>
      </div>
      <div class="text-gray-600 font-medium">Members</div>
    </div>
    <div>
      <div class="text-4xl font-bold text-green-600 mb-2">
        <?php echo (int)db()->query('SELECT COUNT(*) c FROM nfts')->fetch_assoc()['c']; ?>
      </div>
      <div class="text-gray-600 font-medium">Listings</div>
    </div>
    <div>
      <div class="text-4xl font-bold text-pink-600 mb-2">
        ALZ→EUR <?= number_format((float)($settings['alz_to_eur'] ?? 0), 2) ?>
      </div>
      <div class="text-gray-600 font-medium">Rate</div>
    </div>
  </div>
</section>

<!-- Community / Shoutbox -->
<section class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-2 gap-12">
    <div>
      <h2 class="text-4xl font-bold mb-6">Join Our Community</h2>
      <p class="text-xl text-gray-600 mb-8">Chat in real-time with the shoutbox.</p>
      <ul class="space-y-3 text-gray-700 list-disc pl-5">
        <li>Active community</li>
        <li>Creator-friendly platform</li>
        <li>Fast & secure</li>
      </ul>
    </div>
    <div class="bg-gray-50 rounded-2xl p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-2xl font-bold">Community Shoutbox</h3>
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
          <span class="text-sm text-gray-600">Live</span>
        </div>
      </div>
      <div id="shoutbox" class="max-h-80 overflow-y-auto space-y-3 mb-4 bg-white p-3 rounded-lg shadow-inner"></div>
      <div class="flex space-x-2">
        <input type="text" id="shoutInput" placeholder="Share your thoughts..."
               class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        <button onclick="sendShout()"
                class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
      <p class="text-xs text-gray-500 mt-2">You must be logged in to post.</p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/app/views/partials/footer.php'; ?>

<!-- Chatbox HTML -->
<div id="chatbox-container" class="chatbox-container">
  <div id="chatbox-header" class="chatbox-header">
    Chat with us!
    <button id="close-chatbox" class="close-chatbox">&times;</button>
  </div>
  <div id="chatbox-body" class="chatbox-body">
    <!-- Chat messages will appear here -->
  </div>
  <div class="chatbox-footer">
    <textarea id="chat-message" placeholder="Type a message..."></textarea>
    <button id="send-message">Send</button>
  </div>
</div>

<!-- Button to open chatbox -->
<button id="open-chatbox" class="open-chatbox">Chat</button>

<!-- User Suggestions List -->
<div id="user-suggestions" class="user-suggestions"></div>

<!-- Chatbox CSS -->
<style>
/* Chatbox styles */
.chatbox-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 300px;
  height: 400px;
  background-color: #fff;
  border: 1px solid #ccc;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  display: none;
  flex-direction: column;
  z-index: 9999;
}

.chatbox-header {
  background-color: #007bff;
  color: white;
  padding: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: bold;
}

.chatbox-body {
  padding: 10px;
  overflow-y: auto;
  flex-grow: 1;
}

.chatbox-footer {
  display: flex;
  justify-content: space-between;
  padding: 10px;
}

textarea {
  width: 80%;
  height: 40px;
  border-radius: 5px;
  border: 1px solid #ccc;
  padding: 5px;
}

button {
  background-color: #007bff;
  color: white;
  border: none;
  padding: 8px 12px;
  cursor: pointer;
  border-radius: 5px;
}

button:hover {
  background-color: #0056b3;
}

/* User suggestions */
.user-suggestions {
  position: absolute;
  bottom: 80px;
  right: 20px;
  width: 250px;
  background-color: white;
  border: 1px solid #ccc;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  display: none;
  max-height: 150px;
  overflow-y: auto;
  z-index: 10000;
}

.user-suggestions div {
  padding: 8px;
  cursor: pointer;
}

.user-suggestions div:hover {
  background-color: #f0f0f0;
}

/* Floating button to open chatbox */
.open-chatbox {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background-color: #007bff;
  color: white;
  border: none;
  padding: 10px 15px;
  cursor: pointer;
  border-radius: 5px;
}

.open-chatbox:hover {
  background-color: #0056b3;
}

.close-chatbox {
  background: none;
  color: white;
  border: none;
  font-size: 18px;
  cursor: pointer;
}
</style>

<!-- Chatbox JavaScript -->
<script>
// Open the chatbox
document.getElementById('open-chatbox').addEventListener('click', function() {
  document.getElementById('chatbox-container').style.display = 'flex';
  document.getElementById('user-suggestions').style.display = 'none'; // Hide suggestions by default
});

// Close the chatbox
document.getElementById('close-chatbox').addEventListener('click', function() {
  document.getElementById('chatbox-container').style.display = 'none';
});

// Mock data: List of users (in real case, fetch from database)
const users = ["john_doe", "alice_smith", "bob_jones", "charlie_brown", "emily_white"];

// Event listener for typing in the textarea
document.getElementById('chat-message').addEventListener('input', function(event) {
  const message = event.target.value;

  // Check if the user types "@" and suggest usernames
  const atIndex = message.lastIndexOf('@');
  if (atIndex > -1) {
    const query = message.substring(atIndex + 1);
    const filteredUsers = users.filter(user => user.startsWith(query));

    if (filteredUsers.length > 0) {
      const suggestionsHTML = filteredUsers.map(user => `<div class="suggestion-item">${user}</div>`).join('');
      document.getElementById('user-suggestions').innerHTML = suggestionsHTML;
      document.getElementById('user-suggestions').style.display = 'block';
    } else {
      document.getElementById('user-suggestions').style.display = 'none';
    }
  } else {
    document.getElementById('user-suggestions').style.display = 'none';
  }
});

// Event listener for selecting a suggestion
document.getElementById('user-suggestions').addEventListener('click', function(event) {
  if (event.target.classList.contains('suggestion-item')) {
    const selectedUser = event.target.textContent;
    const messageField = document.getElementById('chat-message');
    messageField.value = messageField.value.replace(/@[^ ]*$/, '@' + selectedUser);
    document.getElementById('user-suggestions').style.display = 'none';
  }
});

// Send message
document.getElementById('send-message').addEventListener('click', function() {
  const message = document.getElementById('chat-message').value;

  // Add message to the chatbox (in real case, send it to the server)
  const newMessage = document.createElement('div');
  newMessage.classList.add('chat-message');
  newMessage.textContent = message;
  document.getElementById('chatbox-body').appendChild(newMessage);

  // Clear the message input
  document.getElementById('chat-message').value = '';
});
</script>
