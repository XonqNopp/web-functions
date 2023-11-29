#!/usr/bin/env python3
"""
Script used to write and update the encrypted init file used in PHP.
"""
# TODO docstrings
# TODO codestyle
# TODO logging
# TODO pathlib
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

    ENCRYPTED_FILE = 'functions_local/init_local.aes'

    IV_LENGTH = 16
    SHA_LENGTH = hashlib.sha256().digest_size

    TMP_FILE = '/tmp/il'
    TEMPLATE_FILE = 'functions/templates/init_local.php'

    def __init__(self, debug=False):
        self._debug = debug

        if self._debug > 0:
            print(f'Running with debug mode: {self._debug}')

            if self._debug > 4:
                print('WARNING: no file writing')

        self._tmp_filename = self.TMP_FILE + datetime.strftime(datetime.utcnow(), '%Y%m%d%H%M%S%f')
        self._tmp_filename += '.php'

        if self._debug:
            print(f'TMP: {self._tmp_filename}')

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
            result += f'{ord(char):02x}'

        return result

    def read_key(self):
        """
        Read key from secret file.

        Returns:
            key (str) as plain text
        """
        key = None
        with open(self.KEY_FILE, 'r') as key_file:
            key = key_file.read().strip()

        if self._debug > 4:
            print(f'{key=}')

        if self._debug > 0:
            print(f'{len(key)=}')

        if len(key) > self.KEY_MAXLENGTH:
            raise ValueError(f'Key too long {len(key)}, max allowed by openssl is {self.KEY_MAXLENGTH}')

        return key

    def compute_hmac(self, data, key):
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

        return hmac.new(key, msg=data, digestmod=hashlib.sha256).digest()

    def _openssl(self, key, init_vec, data, encrypt):
        """
        Execute openssl command.

        Args:
            * *key* (str)
            * *init_vec* (str)
            * *data* (bytes)
            * *encrypt* (bool): True to encrypt, False to decrypt

        Returns:
            Output of openssl command (str)
        """
        if isinstance(key, bytes):
            key = key.decode()

        key = self.str2hex(key)

        if isinstance(init_vec, bytes):
            init_vec = init_vec.decode()

        init_vec = self.str2hex(init_vec)

        command = ['openssl', 'enc', '-aes-256-cbc']

        if encrypt:
            command.append('-e')

        else:
            command.append('-d')

        command.extend(['-iv', init_vec])

        if self._debug > 1:
            print(' '.join(command))

        command.extend(['-K', key])

        cmd = subprocess.run(command, check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, input=data)

        return cmd.stdout

    def decrypt(self, key, init_vec, data):
        """
        Decrypt data provided key and IV.

        Args:
            * *key* (str)
            * *init_vec* (str)
            * *data* (bytes)

        Returns:
            decrypted data (str)
        """
        plainData = self._openssl(key, init_vec, data, encrypt=False)

        if self._debug > 4:
            print(f'decrypt={plainData}')

        return plainData

    def encrypt(self, key, init_vec, data):
        """
        Encrypt data provided key and IV.

        Args:
            * *key* (str)
            * *init_vec* (str)
            * *data* (bytes)

        Returns:
            encrypted data (str)
        """
        encryptedData = self._openssl(key, init_vec, data, encrypt=True)

        if self._debug > 1:
            print(f'encrypt={encryptedData}')

        return encryptedData

    def read(self):
        """
        Read encrypted data and get the different parts of it.

        Returns:
            dict(hmac, iv, data) (all are bytes)
        """
        with open(self.ENCRYPTED_FILE, 'r') as f:
            data = base64.decodebytes(f.read().strip().encode())

        init_vec = data[:self.IV_LENGTH]
        hmac = data[self.IV_LENGTH:self.IV_LENGTH + self.SHA_LENGTH]
        realData = data[self.IV_LENGTH + self.SHA_LENGTH:]
        return {'hmac': hmac, 'iv': init_vec, 'encryptedData': realData}

    def write(self, init_vec, hmac, data):
        """
        Compose data and write to encrypted file.

        Args:
            * *init_vec* (str)
            * *hmac* (str)
            * *data* (str)
        """
        if isinstance(init_vec, str):
            init_vec = init_vec.encode()

        if self._debug > 0:
            print(f'{init_vec=}\n{hmac=}')

        if self._debug > 4:
            print('Skipping write file')
            return

        with open(self.ENCRYPTED_FILE, 'w') as f:
            f.write(base64.encodebytes(init_vec + hmac + data).decode())

    def edit(self, plainData, recover=False):
        """
        Edit the plain data.

        Args:
            * *plainData* (str or bytes)
            * *recover* (bool): do not use plain data, only tmp file

        Returns:
            plain data edited (bytes)
        """
        if self._debug > 1:
            print(f'edit({recover=})')

        if not recover:
            if isinstance(plainData, str):
                plainData = plainData.encode()

            # write to tmp file
            with open(self._tmp_filename, 'wb') as tmp:
                tmp.write(plainData)

        # edit
        subprocess.run(['vim', '-n', '-u', 'NONE', self._tmp_filename])

        # read back from tmp file and encode to bytes
        with open(self._tmp_filename, 'r') as tmp:
            newPlainData = tmp.read().strip()

        # delete tmp file
        os.remove(self._tmp_filename)

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

        if self._debug > 4:
            print(f'Skipping writing new password: {p1}')
            return p1

        # Write new password to file
        with open(self.KEY_FILE, 'w') as key_file:
            key_file.write(p1)

        return p1

    def askIV(self, oldIV):
        """
        Ask for initialization vector data.

        Args:
            * *oldIV* (str)

        Returns:
            IV (str)
        """
        init_vec = ''
        valid = False
        while not valid:
            while len(init_vec) < self.IV_LENGTH:
                init_vec += input(f'Provide data to be used as initialization vector (min size={self.IV_LENGTH}): ')

            # Get only required size
            init_vec = init_vec[:self.IV_LENGTH]

            # Check different from previous one
            if init_vec.encode() == oldIV:
                print('IV must not be the same as the old one, try again')
                init_vec = ''
            else:
                valid = True
                break

        return init_vec

    def run(self, recover=False):
        """
        Run the encrypter.

        Args:
            * *recover* (bool): True to use default template file as input
        """
        # init required vars
        init_vec = None
        plainData = None

        # Read key
        key = self.read_key()

        if recover:
            shutil.copy(self.TEMPLATE_FILE, self._tmp_filename)

        else:
            # read encrypted file
            data = self.read()
            encryptedData = data['encryptedData']
            providedHmac = data['hmac']
            init_vec = data['iv']

            # check hmac
            checkHmac = self.compute_hmac(encryptedData, key)
            if providedHmac != checkHmac:
                raise ValueError('HMAC comparison failed')

            # decrypt
            plainData = self.decrypt(key, init_vec, encryptedData)

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
            newIv = self.askIV(init_vec)
            # encrypt
            encryptedData = self.encrypt(key, newIv, newPlainData)
            hmac = self.compute_hmac(encryptedData, key)
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
