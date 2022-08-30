import { Request, Response } from 'express';
import DateParseError from '../errors/DateParseError';
import GranularityError from '../errors/GranularityError';
import JWTError from '../errors/JWTError';
import PermissionsError from '../errors/PermissionsError';
import SendLinkError from '../errors/SendLinkError';
import TenantSearchError from '../errors/TenantSearchError';
import UserCreationError from '../errors/UserCreationError';
import UserDeleteError from '../errors/UserDeleteError';
import UserSearchError from '../errors/UserSearchError';

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

    if (err instanceof JWTError) {
        res.status(401).send({ msg: err.message });
        return;
    }

    if (err instanceof PermissionsError) {
        res.status(403).send({ msg: err.message });
        return;
    }

    res.status(500).send({ msg: err.message });
}
