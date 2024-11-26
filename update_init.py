#!/usr/bin/env python3
"""
Script used to write and update the encrypted init file used in PHP.
"""
import shutil
import subprocess
import base64
import hashlib
import hmac
import getpass
import logging
from datetime import datetime
from argparse import ArgumentParser
from pathlib import Path


class Encrypter:
    """
    Encrypter helper to run same processes as PHP encrypt process.
    """
    KEY_FILE = Path('yptok')
    KEY_MAXLENGTH = 32

    ENCRYPTED_FILE = Path('functions_local', 'init_local.aes')

    IV_LENGTH = 16
    SHA_LENGTH = hashlib.sha256().digest_size

    def __init__(self, dryrun: bool = False) -> None:
        self._dryrun = dryrun
        self._logger = logging.getLogger(self.__class__.__name__)

        if self._dryrun:
            self._logger.error('WARNING: dry run - no file writing')

        self._tmp_file = Path(
            '/tmp',
            'iniloc' + datetime.strftime(datetime.utcnow(), '%Y%m%d%H%M%S%f') + '.php'
        )

        self._logger.warning(f'TMP: {self._tmp_file}')

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
    def compute_hmac(data: bytes, key: bytes) -> bytes:
        """
        Compute HMAC-SHA256 of data.

        Args:
            data (bytes): data to hash
            key (bytes): key to use for HMAC

        Returns:
            bytes: hmac
        """
        return hmac.new(key, msg=data, digestmod=hashlib.sha256).digest()

    def read_key(self) -> bytes:
        """
        Read key from secret file.

        Returns:
            bytes: key as plain text
        """
        key = self.KEY_FILE.read_text().strip().encode()

        if len(key) > self.KEY_MAXLENGTH:
            raise ValueError(
                f'Key too long {len(key)}, max allowed by openssl is {self.KEY_MAXLENGTH}'
            )

        return key

    def _openssl(self, key: bytes, init_vec: bytes, data: bytes, encrypt: bool) -> bytes:
        """
        Execute openssl command.

        Args:
            key (bytes)
            init_vec (bytes)
            data (bytes)
            encrypt (bool): True to encrypt, False to decrypt

        Returns:
            bytes: Output of openssl command
        """
        encrypt_way_flag = '-e' if encrypt else '-d'

        command = [
            'openssl',
            'enc',
            '-aes-256-cbc',
            encrypt_way_flag,
            '-iv',
            self.str2hex(init_vec.decode()),
        ]

        # Log command before adding key
        self._logger.info(' '.join(command))

        command.extend(['-K', self.str2hex(key.decode())])

        cmd = subprocess.run(
            command,
            check=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            input=data,
        )

        return cmd.stdout

    def decrypt(self, key: bytes, init_vec: bytes, data: bytes) -> bytes:
        """
        Decrypt data provided key and IV.

        Args:
            key (bytes)
            init_vec (bytes)
            data (bytes)

        Returns:
            bytes: decrypted data
        """
        return self._openssl(key, init_vec, data, encrypt=False)

    def encrypt(self, key: bytes, init_vec: bytes, data: bytes) -> bytes:
        """
        Encrypt data provided key and IV.

        Args:
            key (bytes)
            init_vec (bytes)
            data (bytes)

        Returns:
            bytes: encrypted data
        """
        encrypted_data = self._openssl(key, init_vec, data, encrypt=True)

        self._logger.debug(f'encrypt={encrypted_data!r}')

        return encrypted_data

    def read(self) -> dict[str, bytes]:
        """
        Read encrypted data and get the different parts of it.

        Returns:
            dict[str, bytes]: hmac, init_vec, data
        """
        data = base64.decodebytes(self.ENCRYPTED_FILE.read_text().strip().encode())

        start = 0
        init_vec = data[start:start + self.IV_LENGTH]

        start = self.IV_LENGTH
        the_hmac = data[start:start + self.SHA_LENGTH]

        start += self.SHA_LENGTH
        encrypted_data = data[start:]

        return {'hmac': the_hmac, 'init_vec': init_vec, 'encrypted_data': encrypted_data}

    def write(self, init_vec: bytes, the_hmac: bytes, data: bytes) -> None:
        """
        Compose data and write to encrypted file.

        Args:
            init_vec (bytes)
            the_hmac (bytes)
            data (bytes)
        """
        if self._dryrun:
            print('Skipping write file')
            return

        self.ENCRYPTED_FILE.write_text(
            base64.encodebytes(init_vec + the_hmac + data).decode()
        )

    def edit(self, plain_data: bytes | None, initialize: bool = False) -> bytes | None:
        """
        Edit the plain data.

        Args:
            plain_data (bytes)
            initialize (bool): do not use plain data, only tmp file

        Returns:
            bytes: plain data edited
        """
        self._logger.info(f'edit(plain_data, {initialize=})')

        if initialize:
            template_file = Path('functions', 'templates', 'init_local.php')
            shutil.copy(template_file, self._tmp_file)

        else:
            if plain_data is None:
                raise ValueError('plain data can be None only for initialize mode')

            self._tmp_file.write_bytes(plain_data)

        # Open editor for user to do the changes
        subprocess.run(['vim', '-n', '-u', 'NONE', str(self._tmp_file)], check=True)

        new_plain_str = self._tmp_file.read_text()
        self._tmp_file.unlink()

        if new_plain_str.startswith('<?php'):
            raise ValueError('You forgot to remove the PHP tags kept for editor formatting, abort')

        new_plain_bytes = new_plain_str.encode()

        if new_plain_bytes == plain_data:
            return None

        return new_plain_bytes

    def change_password(self) -> bytes | None:
        """
        Change password and write to file.

        .. warning:: Do not forget to copy the new key file on the remote server.

        Returns:
            bytes: the new password
        """
        password1 = getpass.getpass('  Enter the new password: ')
        password2 = getpass.getpass('Confirm the new password: ')

        if password1 != password2:
            print('Password mismatch, abort')
            return None

        if self._dryrun:
            print(f'Skipping writing new password: {password1}')
            return password1.encode()

        self.KEY_FILE.write_text(password1)
        print('WARNING: do not forget to copy the new key file on the remote server.')

        return password1.encode()

    def ask_init_vec(self, old_init_vec: bytes | None) -> bytes:
        """
        Ask for initialization vector data.

        Args:
            old_init_vec (bytes)

        Returns:
            str: init vec
        """
        init_vec_str = ''

        while True:
            while len(init_vec_str) < self.IV_LENGTH:
                init_vec_str += input(
                    f'Feed data to be used as initialization vector (size={self.IV_LENGTH}): '
                )

            # Get only required size
            init_vec_str = init_vec_str[:self.IV_LENGTH]
            self._logger.warning(f'{init_vec_str=} ({old_init_vec=!r})')

            init_vec = init_vec_str.encode()
            if init_vec != old_init_vec:
                return init_vec

            print('IV must not be the same as the old one, try again')
            init_vec_str = ''

    def _change_key(self) -> bytes | None:
        """
        Ask if want to change password.

        Returns:
            bytes: new key
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

    def _write(self, key: bytes, init_vec: bytes | None, new_plain_data: bytes) -> None:
        """
        Write the data to the encrypted file.

        Args:
            key (bytes)
            init_vec (bytes)
            new_plain_data (bytes)
        """
        # new_key = self._change_key()  # too dangerous to change this way

        new_key = None
        if new_key is not None:
            key = new_key

        new_iv = self.ask_init_vec(init_vec)

        encrypted_data = self.encrypt(key, new_iv, new_plain_data)
        the_hmac = self.compute_hmac(encrypted_data, key)
        self.write(new_iv, the_hmac, encrypted_data)

    def _write_if_changed(self, key: bytes, init_vec: bytes | None,
                          new_plain_data: bytes | None, plain_data: bytes | None) -> None:
        """
        Write the data to the encrypted file if needed.

        Args:
            key (bytes)
            init_vec (bytes)
            new_plain_data (bytes)
            plain_data (bytes)
        """
        do_you_want_to_write = 'detected. Do you want to write the encrypted file'
        not_writing = 'Not writing. Bye.'

        if new_plain_data is None:
            confirm_write_file = input(f'No modification {do_you_want_to_write} anyway? [y/N]  ')
            if confirm_write_file == '' or confirm_write_file.lower()[0] != 'y':
                print(not_writing)
                return

            if plain_data is None:
                raise ValueError('Cannot have both plain_data and new_plain_data equal to None')

            self._write(key, init_vec, plain_data)
            return

        confirm_write_file = input(f'Modifications {do_you_want_to_write}? [Y/n]  ')
        if confirm_write_file != '' and confirm_write_file.lower()[0] != 'y':
            print(not_writing)
            return

        self._write(key, init_vec, new_plain_data)

    def _read_encrypted_file(self, key: bytes, initialize: bool) -> dict[str, bytes | None]:
        """
        Read the encruypted file.

        Args:
            key (bytes)
            initialize (bool): True to not read but take the default template file

        Returns:
            dict[str, bytes]: dict(plain_data, hmac, init_vec)
        """
        if initialize:
            return {'plain_data': None, 'init_vec': None}

        data = self.read()
        encrypted_data = data['encrypted_data']
        init_vec = data['init_vec']

        check_hmac = self.compute_hmac(encrypted_data, key)
        if data['hmac'] != check_hmac:
            raise ValueError('HMAC comparison failed')

        return {'plain_data': self.decrypt(key, init_vec, encrypted_data), 'init_vec': init_vec}

    def run(self, initialize: bool = False) -> None:
        """
        Run the encrypter.

        Args:
            initialize (bool): True to use default template file as input
        """
        key = self.read_key()

        encrypted_contents = self._read_encrypted_file(key, initialize)
        plain_data = encrypted_contents['plain_data']

        new_plain_data = self.edit(plain_data, initialize)

        if new_plain_data is None and plain_data is None:
            print('Nothing to do. Bye.')
            return

        self._write_if_changed(key, encrypted_contents['init_vec'], new_plain_data, plain_data)


def main() -> None:
    parser = ArgumentParser()

    parser.add_argument(
        '-d',
        '--debug',
        action='count',
        default=0,
        help='debug mode, from level 4 file writing is disabled'
    )

    parser.add_argument(
        '--initialize',
        action='store_true',
        default=False,
        help='to use the default unencrypted file as start'
    )

    args = parser.parse_args()

    level = logging.ERROR
    if args.debug == 1:
        level = logging.WARNING
    elif args.debug == 2:
        level = logging.INFO
    elif args.debug >= 3:
        level = logging.DEBUG

    logging.basicConfig(level=level)

    tool = Encrypter(args.debug > 3)
    tool.run(args.initialize)


if __name__ == '__main__':
    main()
