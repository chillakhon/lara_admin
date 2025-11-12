require('dotenv').config();
const express = require('express');
const { Client } = require('whatsapp-web.js');
const QRCode = require('qrcode');
const axios = require('axios');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors());

// Middleware
app.use(express.json());

// Переменные состояния
let qrCode = null;
let isReady = false;
let client = null;

// ============================================
// SETUP CLIENT EVENTS
// ============================================

function setupClientEvents() {
  // QR Code Event
  client.on('qr', async (qr) => {
    console.log('QR Code received, generating...');
    try {
      qrCode = await QRCode.toDataURL(qr);
      console.log('QR Code generated successfully');
    } catch (err) {
      console.error('Error generating QR code:', err);
    }
  });

  // Ready Event
  client.on('ready', () => {
    console.log('WhatsApp client is ready!');
    isReady = true;
    qrCode = null; // Очищаем QR после успешного подключения
  });

  // Message Event
  client.on('message', async (message) => {
    console.log('Message received:', message.body);

    try {
      // Извлекаем данные сообщения
      const phoneNumber = message.from; // phone_number
      const messageText = message.body; // текст сообщения
      const messageId = message.id.id; // уникальный ID
      const timestamp = new Date(message.timestamp * 1000).toISOString(); // время
      const fromId = message.from; // ID отправителя

      // Отправляем webhook в Laravel
      const webhookData = {
        phone_number: phoneNumber,
        message_text: messageText,
        message_id: messageId,
        timestamp: timestamp,
        from_id: fromId
      };

      console.log('Sending webhook to Laravel:', webhookData);

      const response = await axios.post(
        `${process.env.LARAVEL_API_URL || 'http://localhost:8000'}/api/public/whatsapp/webhook`,
        webhookData,
        {
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          timeout: 10000
        }
      );

      console.log('Laravel webhook response:', response.data);

    } catch (error) {
      console.error('Error processing message:', error.message);
    }
  });

  // Disconnect Event
  client.on('disconnected', (reason) => {
    console.log('Client was logged out', reason);
    isReady = false;
  });
}

// ============================================
// WHATSAPP CLIENT SETUP
// ============================================

// client = new Client({
//   authStrategy: new (require('whatsapp-web.js').LocalAuth)(),
// });


client = new Client({
  authStrategy: new (require('whatsapp-web.js').LocalAuth)(),
  puppeteer: {
    headless: true,
    executablePath: '/usr/bin/chromium-browser',
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-gpu'
    ]
  }
});

setupClientEvents();

// ============================================
// EXPRESS ROUTES
// ============================================

// Получить QR code
app.get('/qr', (req, res) => {
  if (qrCode) {
    return res.json({ qr: qrCode, ready: false });
  }
  if (isReady) {
    return res.json({ qr: null, ready: true });
  }
  return res.json({ qr: null, ready: false, message: 'Initializing...' });
});

// Статус
app.get('/status', (req, res) => {
  res.json({ ready: isReady, hasQR: !!qrCode });
});

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok' });
});


app.post('/send-message', async (req, res) => {
  try {
    const { phone_number, message_text } = req.body;

    if (!phone_number || !message_text) {
      return res.status(400).json({
        success: false,
        error: 'phone_number и message_text обязательны'
      });
    }

    if (!isReady) {
      return res.status(400).json({
        success: false,
        error: 'WhatsApp не подключен'
      });
    }

    console.log(`Sending message to ${phone_number}:`, message_text);

    // Получаем чат по номеру телефона
    const chat = await client.getChatById(phone_number);

    if (!chat) {
      return res.status(400).json({
        success: false,
        error: `Chat not found for phone: ${phone_number}`
      });
    }

    // Отправляем сообщение
    await chat.sendMessage(message_text);

    console.log(`Message sent successfully to ${phone_number}`);

    return res.json({
      success: true,
      message: 'Message sent successfully',
      phone_number: phone_number
    });

  } catch (error) {
    console.error('Error sending message:', error);
    return res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

// Logout / Disconnect
app.post('/logout', async (req, res) => {
  try {
    await client.logout();
    console.log('WhatsApp client logged out');
    isReady = false;
    qrCode = null;

    // Пересоздаём client для нового подключения
    await client.destroy();
    client = new Client({
      authStrategy: new (require('whatsapp-web.js').LocalAuth)(),
    });

    // Переподключаем события
    setupClientEvents();

    // Инициализируем заново
    client.initialize();

    res.json({ success: true, message: 'Logged out successfully' });
  } catch (error) {
    console.error('Error during logout:', error);
    res.status(500).json({ success: false, error: error.message });
  }
});

// ============================================
// SERVER START
// ============================================

app.listen(PORT, () => {
  console.log(`WhatsApp service running on port ${PORT}`);
});

client.initialize();

// Graceful shutdown
process.on('SIGINT', async () => {
  console.log('Shutting down...');
  await client.destroy();
  process.exit(0);
});
