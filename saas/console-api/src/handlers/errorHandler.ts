import { Request, Response } from 'express';
import DateParseError from '../errors/DateParseError';
import MissingJWTError from '../errors/MissingJWTError';
import PermissionsError from '../errors/PermissionsError';
import GranularityError from '../errors/GranularityError';
import UserCreationError from '../errors/UserCreationError';
import UserSearchError from '../errors/UserSearchError';
import TenantSearchError from '../errors/TenantSearchError';
import SendLinkError from '../errors/SendLinkError';
import UserDeleteError from '../errors/UserDeleteError';

export default function handleError(err: Error, req: Request, res: Response): void {
  if (err instanceof DateParseError || err instanceof GranularityError) {
    res.status(400).send({ msg: err.message, code: err.code });
    return;
  }

  if (
    err instanceof UserCreationError
    || err instanceof UserSearchError
    || err instanceof TenantSearchError
    || err instanceof UserDeleteError
    || err instanceof SendLinkError
  ) {
    res.status(400).send({ msg: err.message });
    return;
  }

  if (err instanceof MissingJWTError) {
    res.status(401).send({ msg: err.message });
    return;
  }

  if (err instanceof PermissionsError) {
    res.status(403).send({ msg: err.message });
    return;
  }

  res.status(500).send({ msg: err.message });
}
