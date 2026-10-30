const AP_VERSION = { major: 0, minor: 6, build: 0, class: 'Version' };

export default class ArchipelagoClient {
  constructor() {
    this.ws = null;
    this.onStatusChange = null;
    this.onMessage = null;
    this.onError = null;
    this.onLocationChecked = null;
    this.onSeedName = null;
    this.onGameDetected = null;
    this._reconnectTimer = null;
    this._locationMap = {};
    this._checkedLocations = new Set();
    this._game = '';
    this._slot = null;
    this._team = null;
  }

  connect(hostname, port, slotName, password) {
    if (this.ws) {
      this.disconnect();
    }

    this._hostname = hostname;
    this._port = port;
    this._slotName = slotName;
    this._password = password || '';
    this._locationMap = {};
    this._checkedLocations = new Set();

    const scheme = (port === 80 || hostname === 'localhost' || hostname === '127.0.0.1') ? 'ws' : 'wss';
    const url = `${scheme}://${hostname}:${port}`;

    this._setStatus('connecting');
    this._log(`Connecting to ${url}...`);

    try {
      this.ws = new WebSocket(url);
    } catch (e) {
      this._setStatus('error', e.message);
      return;
    }

    this.ws.onopen = () => {
      this._log('WebSocket connected, waiting for RoomInfo...');
    };

    this.ws.onmessage = (event) => {
      let packets;
      try {
        packets = JSON.parse(event.data);
      } catch {
        this._log(`[raw] ${event.data}`);
        return;
      }

      if (!Array.isArray(packets)) {
        packets = [packets];
      }

      for (const packet of packets) {
        this._handlePacket(packet);
      }
    };

    this.ws.onerror = () => {
      this._setStatus('error', 'WebSocket error');
      this._log('WebSocket error');
    };

    this.ws.onclose = (event) => {
      this._log(`Disconnected (code: ${event.code})`);
      this._setStatus('disconnected');
      this.ws = null;
    };
  }

  disconnect() {
    clearTimeout(this._reconnectTimer);
    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }
    this._setStatus('disconnected');
  }

  isConnected() {
    return this.ws?.readyState === WebSocket.OPEN;
  }

  _send(packets) {
    if (!this.isConnected()) return;
    this.ws.send(JSON.stringify(Array.isArray(packets) ? packets : [packets]));
  }

  _handlePacket(packet) {
    const cmd = packet.cmd;

    switch (cmd) {
      case 'RoomInfo':
        this._roomGames = (packet.games || []).filter(g => g !== 'Archipelago');
        this._datapackageChecksums = packet.datapackage_checksums || {};
        this._seedName = packet.seed_name || '';
        this._log(`[RoomInfo] Games: ${this._roomGames.join(', ')}, Seed: ${this._seedName}`);
        if (this._seedName && this.onSeedName) {
          this.onSeedName(this._seedName);
        }
        this._sendConnect();
        break;

      case 'Connected':
        this._setStatus('connected');
        this._slot = packet.slot;
        this._team = packet.team;
        this._game = packet.slot_info?.[packet.slot]?.game || this._roomGames[0] || '';
        this._log(`Connected to slot "${this._slotName}" (${this._game}) — team ${packet.team}, slot ${packet.slot}`);
        if (this._game && this.onGameDetected) {
          this.onGameDetected(this._game);
        }

        if (packet.checked_locations?.length) {
          this._log(`${packet.checked_locations.length} locations already checked`);
          this._pendingCheckedLocations = packet.checked_locations;
        }

        this._send({
          cmd: 'GetDataPackage',
          games: this._game ? [this._game] : undefined,
        });
        break;

      case 'ConnectionRefused':
        this._setStatus('error', (packet.errors || []).join(', ') || 'Connection refused');
        this._log(`Connection refused: ${(packet.errors || []).join(', ')}`);
        break;

      case 'ReceivedItems':
        this._log(`Received ${packet.items?.length || 0} items (index ${packet.index})`);
        break;

      case 'RoomUpdate':
        if (packet.checked_locations?.length) {
          this._processCheckedLocations(packet.checked_locations);
        }
        break;

      case 'PrintJSON':
        this._logPrintJSON(packet);
        break;

      case 'DataPackage':
        this._processDataPackage(packet.data);
        break;

      case 'Bounced':
        break;

      default:
        this._log(`[${cmd}] ${JSON.stringify(packet).substring(0, 300)}`);
        break;
    }
  }

  _sendConnect() {
    const game = this._roomGames.length === 1 ? this._roomGames[0] : '';
    this._send({
      cmd: 'Connect',
      password: this._password,
      game,
      name: this._slotName,
      uuid: crypto.randomUUID?.() || Math.random().toString(36).substring(2),
      version: AP_VERSION,
      items_handling: 0b111,
      tags: ['TextOnly'],
      slot_data: true,
    });
  }

  _processDataPackage(data) {
    if (!data) return;

    for (const [gameName, gameData] of Object.entries(data.games || data)) {
      const locations = gameData.location_name_to_id || {};
      for (const [name, id] of Object.entries(locations)) {
        this._locationMap[id] = name;
      }
      this._log(`Loaded ${Object.keys(locations).length} locations for ${gameName}`);
    }

    if (this._pendingCheckedLocations) {
      this._processCheckedLocations(this._pendingCheckedLocations);
      this._pendingCheckedLocations = null;
    }
  }

  _processCheckedLocations(locationIds) {
    for (const id of locationIds) {
      if (this._checkedLocations.has(id)) continue;
      this._checkedLocations.add(id);

      const name = this._locationMap[id];
      if (!name) continue;

      const caught = this._parsePokemonCheck(name, 'Pokedex - ');
      if (caught) {
        this._log(`✓ Caught: ${caught}`);
        if (this.onLocationChecked) {
          this.onLocationChecked({ pokemon: caught, type: 'caught' });
        }
        continue;
      }

      const seen = this._parsePokemonCheck(name, 'Dexsanity - ');
      if (seen) {
        this._log(`○ Seen: ${seen}`);
        if (this.onLocationChecked) {
          this.onLocationChecked({ pokemon: seen, type: 'seen' });
        }
      }
    }
  }

  _parsePokemonCheck(locationName, prefix) {
    if (locationName.toLowerCase().startsWith(prefix.toLowerCase())) {
      return locationName.slice(prefix.length);
    }
    return null;
  }

  _logPrintJSON(packet) {
    if (!packet.data) return;
    const text = packet.data.map(part => part.text || '').join('');
    if (text.trim()) {
      this._log(text);
    }
  }

  _setStatus(status, error) {
    if (this.onStatusChange) {
      this.onStatusChange(status, error || '');
    }
  }

  _log(message) {
    console.log('[AP]', message);
    if (this.onMessage) {
      this.onMessage(message);
    }
  }
}
