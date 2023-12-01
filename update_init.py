#!/usr/bin/env python3
"""
Script used to write and update the encrypted init file used in PHP.
"""
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
from argparse import ArgumentParser


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

    ENCODING = 'utf-8'

    def __init__(self, debug: bool = False) -> None:
        self._debug = debug

        if self._debug > 0:
            print(f'Running with debug mode: {self._debug}')

            if self._debug > 4:
                print('WARNING: no file writing')

        self._tmp_filename = self.TMP_FILE + datetime.strftime(datetime.utcnow(), '%Y%m%d%H%M%S%f')
        self._tmp_filename += '.php'

        if self._debug:
            print(f'TMP: {self._tmp_filename}')

    @staticmethod
    def str2hex(string: str) -> str:
        """
        Convert string to hex"

        Args:
            string (str)

        Returns:
            str: hex format of string
        """
        result = ''
        for char in string:
            result += f'{ord(char):02x}'

        return result

    @staticmethod
    def compute_hmac(data: str | bytes, key: str | bytes) -> None:
        """
        Compute HMAC-SHA256 of data.

        Args:
            data (str, bytes): data to hash
            key (str, bytes): key to use for HMAC

        Returns:
            bytes: hmac
        """
        if isinstance(data, str):
            data = data.encode()
        if isinstance(key, str):
            key = key.encode()

        return hmac.new(key, msg=data, digestmod=hashlib.sha256).digest()

    def read_key(self) -> str:
        """
        Read key from secret file.

        Returns:
            str: key as plain text
        """
        key = None
        with open(self.KEY_FILE, 'r', encoding=self.ENCODING) as key_file:
            key = key_file.read().strip()

        if self._debug > 4:
            print(f'{key=}')

        if self._debug > 0:
            print(f'{len(key)=}')

        if len(key) > self.KEY_MAXLENGTH:
            raise ValueError(
                f'Key too long {len(key)}, max allowed by openssl is {self.KEY_MAXLENGTH}'
            )

        return key

    def _openssl(self, key: str, init_vec: str, data: bytes, encrypt: bool) -> str:
        """
        Execute openssl command.

        Args:
            key (str)
            init_vec (str)
            data (bytes)
            encrypt (bool): True to encrypt, False to decrypt

        Returns:
            str: Output of openssl command
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

        cmd = subprocess.run(
            command,
            check=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            input=data,
        )

        return cmd.stdout

    def decrypt(self, key: str, init_vec: str, data: bytes) -> str:
        """
        Decrypt data provided key and IV.

        Args:
            key (str)
            init_vec (str)
            data (bytes)

        Returns:
            str: decrypted data
        """
        plain_data = self._openssl(key, init_vec, data, encrypt=False)

        if self._debug > 4:
            print(f'decrypt={plain_data}')

        return plain_data

    def encrypt(self, key: str, init_vec: str, data: bytes) -> str:
        """
        Encrypt data provided key and IV.

        Args:
            key (str)
            init_vec (str)
            data (bytes)

        Returns:
            str: encrypted data
        """
        encrypted_data = self._openssl(key, init_vec, data, encrypt=True)

        if self._debug > 1:
            print(f'encrypt={encrypted_data}')

        return encrypted_data

    def read(self) -> dict[str, bytes]:
        """
        Read encrypted data and get the different parts of it.

        Returns:
            dict[str bytes]: hmac, iv, data
        """
        with open(self.ENCRYPTED_FILE, 'r', encoding=self.ENCODING) as file_handle:
            data = base64.decodebytes(file_handle.read().strip().encode())

        init_vec = data[:self.IV_LENGTH]
        the_hmac = data[self.IV_LENGTH:self.IV_LENGTH + self.SHA_LENGTH]
        real_data = data[self.IV_LENGTH + self.SHA_LENGTH:]
        return {'hmac': the_hmac, 'iv': init_vec, 'encrypted_data': real_data}

    def write(self, init_vec: str, the_hmac: str, data: str) -> None:
        """
        Compose data and write to encrypted file.

        Args:
            init_vec (str)
            the_hmac (str)
            data (str)
        """
        if isinstance(init_vec, str):
            init_vec = init_vec.encode()

        if self._debug > 0:
            print(f'{init_vec=}\n{the_hmac=}')

        if self._debug > 4:
            print('Skipping write file')
            return

        with open(self.ENCRYPTED_FILE, 'w', encoding=self.ENCODING) as file_handle:
            file_handle.write(base64.encodebytes((init_vec + the_hmac + data).encode()).decode())

    def edit(self, plain_data: str | bytes, recover: bool = False) -> bytes | None:
        """
        Edit the plain data.

        Args:
            plain_data (str, bytes)
            recover (bool): do not use plain data, only tmp file

        Returns:
            bytes: plain data edited
        """
        if self._debug > 1:
            print(f'edit({recover=})')

        if not recover:
            if isinstance(plain_data, str):
                plain_data = plain_data.encode()

            # write to tmp file
            with open(self._tmp_filename, 'wb') as tmp:
                tmp.write(plain_data)

        # Open editor for user to do the changes
        subprocess.run(['vim', '-n', '-u', 'NONE', self._tmp_filename], check=True)

        # read back from tmp file and encode to bytes
        with open(self._tmp_filename, 'r', encoding=self.ENCODING) as tmp:
            new_plain_data = tmp.read().strip()

        # delete tmp file
        os.remove(self._tmp_filename)

        # Check if PHP tags present
        if new_plain_data.startswith('<?php'):
            raise ValueError('You forgot to remove the PHP tags kept for editor formatting, abort')

        # Convert to bytes
        new_plain_data = new_plain_data.encode()

        # check if modifications
        if new_plain_data == plain_data:
            return None

        return new_plain_data

    def change_password(self) -> str | None:
        """
        Change password and write to file.

        .. warning:: Do not forget to copy the new key file on the remote server.

        Returns:
            str: the new password
        """
        password1 = getpass.getpass('  Enter the new password: ')
        password2 = getpass.getpass('Confirm the new password: ')
        if password1 != password2:
            return None

        if self._debug > 4:
            print(f'Skipping writing new password: {password1}')
            return password1

        # Write new password to file
        with open(self.KEY_FILE, 'w', encoding=self.ENCODING) as key_file:
            key_file.write(password1)

        return password1

    def ask_init_vec(self, old_init_vec: str) -> str:
        """
        Ask for initialization vector data.

        Args:
            old_init_vec (str)

        Returns:
            str: IV
        """
        init_vec = ''

        while True:
            while len(init_vec) < self.IV_LENGTH:
                init_vec += input(
                    f'Feed data to be used as initialization vector (min size={self.IV_LENGTH}): '
                )

            # Get only required size
            init_vec = init_vec[:self.IV_LENGTH]

            # Check different from previous one
            if init_vec.encode() != old_init_vec:
                return init_vec

            print('IV must not be the same as the old one, try again')
            init_vec = ''

    def _change_key(self) -> str | None:
        """
        Ask if want to change password.

        Returns:
            str: new key
        """
        change_key = input('Do you want to change password? [y/N]  ')

        if change_key == '' or change_key.lower()[0] != 'y':
            return None

        new_key = self.change_password()
        if new_key is None:
            print('Failed to get new password, using previous one')
            return None

        print('Using new password, do not forget to update secret file on remote server.')
        return new_key

    def _write(self, key: str, init_vec: str, new_plain_data: str) -> None:
        """
        Write the data to the encrypted file.

        Args:
            key (str)
            init_vec (str)
            new_plain_data (str)
        """
        # new_key = self._change_key()  # too dangerous to change this way
        new_key = None
        if new_key is not None:
            key = new_key

        # ask IV
        new_iv = self.ask_init_vec(init_vec)
        # encrypt
        encrypted_data = self.encrypt(key, new_iv, new_plain_data)
        the_hmac = self.compute_hmac(encrypted_data, key)
        self.write(new_iv, the_hmac, encrypted_data)

    def _write_if_changed(self, key: str, init_vec: str, new_plain_data: str, plain_data: str) -> None:
        """
        Write the data to the encrypted file if needed.

        Args:
            key (str)
            init_vec (str)
            new_plain_data (str)
            plain_data (str)
        """
        do_you_want_to_write = 'detected. Do you want to write the encrypted file'
        if new_plain_data is not None:
            confirm_write_file = input(f'Modifications {do_you_want_to_write}? [Y/n]  ')
            if confirm_write_file.lower()[0] not in ['', 'y']:
                return

        else:
            confirm_write_file = input(f'No modification {do_you_want_to_write} anyway? [y/N]  ')
            if confirm_write_file.lower()[0] != 'y':
                return

            # Need to store old plain data as new
            new_plain_data = plain_data

        self._write(key, init_vec, new_plain_data)

    def _read_encrypted_file(self, key: str) -> str:
        """
        Read the encruypted file.

        Args:
            key (str)

        Returns:
            str: decrypted content
        """
        data = self.read()
        encrypted_data = data['encrypted_data']
        provided_hmac = data['hmac']
        init_vec = data['iv']

        # check hmac
        check_hmac = self.compute_hmac(encrypted_data, key)
        if provided_hmac != check_hmac:
            raise ValueError('HMAC comparison failed')

        return self.decrypt(key, init_vec, encrypted_data)

    def run(self, recover: bool = False) -> None:
        """
        Run the encrypter.

        Args:
            recover (bool): True to use default template file as input
        """
        # init required vars
        init_vec = None
        plain_data = None

        # Read key
        key = self.read_key()

        if recover:
            shutil.copy(self.TEMPLATE_FILE, self._tmp_filename)

        else:
            plain_data = self._read_encrypted_file(key)

        new_plain_data = self.edit(plain_data, recover)

        self._write_if_changed(key, init_vec, new_plain_data, plain_data)


def main() -> None:
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


if __name__ == '__main__':
    main()
