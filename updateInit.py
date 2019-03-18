#!/usr/bin/env python3
import os
import shutil
import subprocess
import base64
import hashlib
import hmac
import getpass
from datetime import datetime


class Encrypter:
    """
    Encrypter helper to run same processes as PHP encrypt process.
    """
    KEY_FILE = 'yptok'
    KEY_MAXLENGTH = 32

    ENCRYPTED_FILE = 'functions_local/initLocal.aes'

    IV_LENGTH = 16
    SHA_LENGTH = hashlib.sha256().digest_size

    TMP_FILE = '/tmp/il'
    TEMPLATE_FILE = 'functions/initLocal.php'

    def __init__(self, debug=False):
        self.DEBUG = debug

        if self.DEBUG > 0:
            print('Running with debug mode: {}'.format(self.DEBUG))

            if self.DEBUG > 4:
                print('WARNING: no file writing')

        self._tmpFilename = self.TMP_FILE + datetime.strftime(datetime.utcnow(), '%Y%m%d%H%M%S%f') + '.php'
        if self.DEBUG:
            print('TMP: {}'.format(self._tmpFilename))

    def str2hex(self, string):
        """
        Convert string to hex"

        Args:
            * *string* (str)

        Returns:
            hex format of string (str)
        """
        result = ''
        for char in string:
            result += '{:02x}'.format(ord(char))

        return result

    def readKey(self):
        """
        Read key from secret file.

        Returns:
            key (str) as plain text
        """
        key = None
        with open(self.KEY_FILE, 'r') as keyFile:
            key = keyFile.read().strip()

        if self.DEBUG > 4:
            print('key={}'.format(key))

        if self.DEBUG > 0:
            print('len(key)={}'.format(len(key)))

        if len(key) > self.KEY_MAXLENGTH:
            raise ValueError('Key too long {}, max allowed by openssl is {}'.format(len(key), self.KEY_MAXLENGTH))

        return key

    def computeHmac(self, data, key):
        """
        Compute HMAC-SHA256 of data.

        Args:
            * *data* (str or bytes)

        Returns:
            bytes
        """
        if isinstance(data, str):
            data = data.encode()
        if isinstance(key, str):
            key = key.encode()

        dataHmac = hmac.new(key, msg=data, digestmod=hashlib.sha256).digest()
        return dataHmac

    def _openssl(self, key, iv, data, encrypt):
        """
        Execute openssl command.

        Args:
            * *key* (str)
            * *iv* (str)
            * *data* (bytes)
            * *encrypt* (bool): True to encrypt, False to decrypt

        Returns:
            Output of openssl command (str)
        """
        if isinstance(key, bytes):
            key = key.decode()

        key = self.str2hex(key)

        if isinstance(iv, bytes):
            iv = iv.decode()

        iv = self.str2hex(iv)

        command = ['openssl', 'enc', '-aes-256-cbc']

        if encrypt:
            command.append('-e')

        else:
            command.append('-d')

        command.extend(['-iv', iv])

        if self.DEBUG > 1:
            print(' '.join(command))

        command.extend(['-K', key])

        cmd = subprocess.run(command, check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, input=data)

        return cmd.stdout

    def decrypt(self, key, iv, data):
        """
        Decrypt data provided key and IV.

        Args:
            * *key* (str)
            * *iv* (str)
            * *data* (bytes)

        Returns:
            decrypted data (str)
        """
        plainData = self._openssl(key, iv, data, encrypt=False)

        if self.DEBUG > 4:
            print('decrypt={}'.format(plainData))

        return plainData

    def encrypt(self, key, iv, data):
        """
        Encrypt data provided key and IV.

        Args:
            * *key* (str)
            * *iv* (str)
            * *data* (bytes)

        Returns:
            encrypted data (str)
        """
        encryptedData = self._openssl(key, iv, data, encrypt=True)

        if self.DEBUG > 1:
            print('encrypt={}'.format(encryptedData))

        return encryptedData

    def read(self):
        """
        Read encrypted data and get the different parts of it.

        Returns:
            dict(hmac, iv, data) (all are bytes)
        """
        with open(self.ENCRYPTED_FILE, 'r') as f:
            data = base64.decodebytes(f.read().strip().encode())

        iv = data[:self.IV_LENGTH]
        hmac = data[self.IV_LENGTH:self.IV_LENGTH + self.SHA_LENGTH]
        realData = data[self.IV_LENGTH + self.SHA_LENGTH:]
        return {'hmac': hmac, 'iv': iv, 'encryptedData': realData}

    def write(self, iv, hmac, data):
        """
        Compose data and write to encrypted file.

        Args:
            * *iv* (str)
            * *hmac* (str)
            * *data* (str)
        """
        if isinstance(iv, str):
            iv = iv.encode()

        if self.DEBUG > 0:
            print('iv={}'.format(iv))
            print('hmac={}'.format(hmac))

        if self.DEBUG > 4:
            print('Skipping write file')
            return

        with open(self.ENCRYPTED_FILE, 'w') as f:
            f.write(base64.encodebytes(iv + hmac + data).decode())

    def edit(self, plainData, recover=False):
        """
        Edit the plain data.

        Args:
            * *plainData* (str or bytes)
            * *recover* (bool): do not use plain data, only tmp file

        Returns:
            plain data edited (bytes)
        """
        if self.DEBUG > 1:
            print('edit(recover={})'.format(recover))

        if not recover:
            if isinstance(plainData, str):
                plainData = plainData.encode()

            # write to tmp file
            with open(self._tmpFilename, 'wb') as tmp:
                tmp.write(plainData)

        # edit
        subprocess.run(['vim', '-n', '-u', 'NONE', self._tmpFilename])

        # read back from tmp file and encode to bytes
        with open(self._tmpFilename, 'r') as tmp:
            newPlainData = tmp.read().strip()

        # delete tmp file
        os.remove(self._tmpFilename)

        # Check if PHP tags present
        if newPlainData.startswith('<?php'):
            raise ValueError('You forgot to remove the PHP tags kept for editor formatting, abort')

        # Convert to bytes
        newPlainData = newPlainData.encode()

        # check if modifications
        if newPlainData == plainData:
            newPlainData = None

        return newPlainData

    def changePassword(self):
        """
        Change password and write to file.

        .. warning:: Do not forget to copy the new key file on the remote server.

        Returns:
            the new password
        """
        p1 = getpass.getpass('  Enter the new password: ')
        p2 = getpass.getpass('Confirm the new password: ')
        if p1 != p2:
            return None

        if self.DEBUG > 4:
            print('Skipping writing new password: {}'.format(p1))
            return p1

        # Write new password to file
        with open(self.KEY_FILE, 'w') as keyFile:
            keyFile.write(p1)

        return p1

    def askIV(self, oldIV):
        """
        Ask for initialization vector data.

        Args:
            * *oldIV* (str)

        Returns:
            IV (str)
        """
        iv = ''
        valid = False
        while not valid:
            while len(iv) < self.IV_LENGTH:
                iv += input('Provide data to be used as initialization vector (min size={}): '.format(self.IV_LENGTH))

            # Get only required size
            iv = iv[:self.IV_LENGTH]

            # Check different from previous one
            if iv.encode() == oldIV:
                print('IV must not be the same as the old one, try again')
                iv = ''
            else:
                valid = True
                break

        return iv

    def run(self, recover=False):
        """
        Run the encrypter.

        Args:
            * *recover* (bool): True to use default template file as input
        """
        # init required vars
        iv = None
        plainData = None

        # Read key
        key = self.readKey()

        if recover:
            shutil.copy(self.TEMPLATE_FILE, self._tmpFilename)

        else:
            # read encrypted file
            data = self.read()
            encryptedData = data['encryptedData']
            providedHmac = data['hmac']
            iv = data['iv']

            # check hmac
            checkHmac = self.computeHmac(encryptedData, key)
            if providedHmac != checkHmac:
                raise ValueError('HMAC comparison failed')

            # decrypt
            plainData = self.decrypt(key, iv, encryptedData)

        # edit
        newPlainData = self.edit(plainData, recover)

        # check if changed
        writeFile = False
        if newPlainData is not None:
            confirmWriteFile = input('Modifications detected. Do you want to write the encrypted file? [Y/n]  ')
            writeFile = bool(confirmWriteFile == '' or confirmWriteFile.lower()[0] == 'y')
        else:
            confirmWriteFile = input('No modification detected. Do you want to write encrypted file anyway? [y/N]  ')
            writeFile = bool(confirmWriteFile != '' and confirmWriteFile.lower()[0] == 'y')
            if writeFile:
                # Need to store old plain data as new
                newPlainData = plainData

        # Write procedure
        if writeFile:
            # ask if want to change password
            #changeKey = input('Do you want to change password? [y/N]  ')

            changeKey = ''  # Too dangerous to change this way
            if changeKey != '' and changeKey.lower()[0] == 'y':
                newKey = self.changePassword()
                if newKey is None:
                    print('Failed to get new password, using previous one')
                else:
                    print('Using new password, do not forget to update secret file on remote server.')
                    key = newKey

            # ask IV
            newIv = self.askIV(iv)
            # encrypt
            encryptedData = self.encrypt(key, newIv, newPlainData)
            hmac = self.computeHmac(encryptedData, key)
            self.write(newIv, hmac, encryptedData)


if __name__ == '__main__':
    from argparse import ArgumentParser
    parser = ArgumentParser()
    parser.add_argument(
        '-d',
        '--debug',
        action='count',
        default=0,
        help='debug mode, from level 5 file writing is disabled'
    )
    parser.add_argument(
        '--recover',
        action='store_true',
        default=False,
        help='to use the default unencrypted file as start'
    )
    args = parser.parse_args()

    tool = Encrypter(args.debug)
    tool.run(args.recover)

