#!/usr/bin/env python3
"""
Cadman Manufacturing - Configuration Directory Encryption Tool
Encrypts/decrypts the entire config directory containing API credentials
"""

import os
import sys
import getpass
import base64
import json
from pathlib import Path

# Add the virtual environment to Python path
sys.path.insert(0, '/var/www/config_encryption_env/lib/python3.11/site-packages')

from cryptography.fernet import Fernet
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2HMAC

class ConfigDirectoryEncryption:
    def __init__(self, config_dir="/var/www/config"):
        self.config_dir = Path(config_dir)
        self.encrypted_dir = Path(f"{config_dir}_encrypted")
        
    def generate_key_from_password(self, password: str, salt: bytes) -> bytes:
        """Generate encryption key from password using PBKDF2"""
        password_bytes = password.encode()
        kdf = PBKDF2HMAC(
            algorithm=hashes.SHA256(),
            length=32,
            salt=salt,
            iterations=100000,
        )
        key = base64.urlsafe_b64encode(kdf.derive(password_bytes))
        return key
    
    def encrypt_directory(self, password: str):
        """Encrypt entire config directory"""
        print(f"🔒 Encrypting config directory: {self.config_dir}")
        
        # Generate salt and key
        salt = os.urandom(16)
        key = self.generate_key_from_password(password, salt)
        fernet = Fernet(key)
        
        # Create encrypted directory
        self.encrypted_dir.mkdir(exist_ok=True)
        
        # Store metadata
        metadata = {
            "salt": base64.b64encode(salt).decode(),
            "encrypted_files": []
        }
        
        # Encrypt all files in config directory
        for file_path in self.config_dir.rglob('*'):
            if file_path.is_file():
                relative_path = file_path.relative_to(self.config_dir)
                
                # Read file content
                with open(file_path, 'rb') as f:
                    file_content = f.read()
                
                # Encrypt content
                encrypted_content = fernet.encrypt(file_content)
                
                # Save encrypted file
                encrypted_file_path = self.encrypted_dir / f"{relative_path}.encrypted"
                encrypted_file_path.parent.mkdir(parents=True, exist_ok=True)
                
                with open(encrypted_file_path, 'wb') as f:
                    f.write(encrypted_content)
                
                metadata["encrypted_files"].append({
                    "original": str(relative_path),
                    "encrypted": str(f"{relative_path}.encrypted")
                })
                
                print(f"  ✅ Encrypted: {relative_path}")
        
        # Save metadata
        metadata_file = self.encrypted_dir / "encryption_metadata.json"
        with open(metadata_file, 'w') as f:
            json.dump(metadata, f, indent=2)
        
        print(f"📁 Encrypted directory created: {self.encrypted_dir}")
        print(f"🔑 Total files encrypted: {len(metadata['encrypted_files'])}")
        
        return True
    
    def decrypt_directory(self, password: str):
        """Decrypt config directory"""
        print(f"🔓 Decrypting config directory from: {self.encrypted_dir}")
        
        if not self.encrypted_dir.exists():
            print(f"❌ Encrypted directory not found: {self.encrypted_dir}")
            return False
        
        # Load metadata
        metadata_file = self.encrypted_dir / "encryption_metadata.json"
        if not metadata_file.exists():
            print("❌ Encryption metadata not found!")
            return False
        
        with open(metadata_file, 'r') as f:
            metadata = json.load(f)
        
        # Get salt and generate key
        salt = base64.b64decode(metadata["salt"])
        key = self.generate_key_from_password(password, salt)
        fernet = Fernet(key)
        
        # Create config directory
        self.config_dir.mkdir(exist_ok=True)
        
        # Decrypt all files
        for file_info in metadata["encrypted_files"]:
            encrypted_file_path = self.encrypted_dir / file_info["encrypted"]
            original_file_path = self.config_dir / file_info["original"]
            
            try:
                # Read encrypted content
                with open(encrypted_file_path, 'rb') as f:
                    encrypted_content = f.read()
                
                # Decrypt content
                decrypted_content = fernet.decrypt(encrypted_content)
                
                # Create directory if needed
                original_file_path.parent.mkdir(parents=True, exist_ok=True)
                
                # Write decrypted file
                with open(original_file_path, 'wb') as f:
                    f.write(decrypted_content)
                
                # Set secure permissions
                os.chmod(original_file_path, 0o600)
                
                print(f"  ✅ Decrypted: {file_info['original']}")
                
            except Exception as e:
                print(f"  ❌ Failed to decrypt {file_info['original']}: {e}")
                return False
        
        print(f"📁 Decrypted directory restored: {self.config_dir}")
        return True
    
    def status(self):
        """Show encryption status"""
        config_exists = self.config_dir.exists()
        encrypted_exists = self.encrypted_dir.exists()
        
        if config_exists and not encrypted_exists:
            print("🔓 UNENCRYPTED")
        elif not config_exists and encrypted_exists:
            print("🔒 ENCRYPTED")
        elif config_exists and encrypted_exists:
            print("⚠️  BOTH EXIST")
        else:
            print("❌ NEITHER EXISTS")

def main():
    if len(sys.argv) < 2:
        print("Usage: python3 config_encrypt.py [encrypt|decrypt|status]")
        sys.exit(1)
    
    command = sys.argv[1].lower()
    encryption_tool = ConfigDirectoryEncryption()
    
    if command == "status":
        encryption_tool.status()
    elif command == "encrypt":
        password = getpass.getpass("Enter encryption password: ")
        encryption_tool.encrypt_directory(password)
        print("✅ Encryption completed!")
    elif command == "decrypt":
        password = getpass.getpass("Enter decryption password: ")
        if encryption_tool.decrypt_directory(password):
            print("✅ Decryption completed!")
        else:
            print("❌ Decryption failed!")

if __name__ == "__main__":
    main()