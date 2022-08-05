import { Request } from 'express';
import { decode } from 'jsonwebtoken';
import MissingJWTError from '../errors/MissingJWTError';

export const AUTHORIZATION = 'authorization';

export interface IJWTPayload {
  /* eslint-disable @typescript-eslint/naming-convention */
  firebase: { tenant: string },
  first_name: string,
  last_name: string,
  email: string,
  permissions: string[],
  /* eslint-enable @typescript-eslint/naming-convention */
}

export function getLoggedUser(req: Request): string {
  const authorization = req.get(AUTHORIZATION);
  if (authorization) {
    const decoded = decode(authorization) as IJWTPayload;
    // eslint-disable-next-line @typescript-eslint/naming-convention
    if (decoded?.firebase?.tenant) {
      return decoded.firebase.tenant;
    }
  }

  throw new MissingJWTError();
}

export function getLoggedUserPermissions(req: Request): string[] {
  const authorization = req.get(AUTHORIZATION);
  if (authorization) {
    const decoded = decode(authorization) as IJWTPayload;
    return decoded.permissions ?? [];
  }

  throw new MissingJWTError();
}

export function hasPermission(permissions: string[], resource: string): boolean {
  return permissions.includes(resource);
}
