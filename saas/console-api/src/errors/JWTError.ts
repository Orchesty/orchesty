export const MISSING_JWT_TOKEN = 'JWT token is missing in request header!';
export const ERROR_PARSING_AUTHORIZATION_HEADER = 'Can\'t parse Authorization header';
export const BAD_JWT_PAYLOAD = 'Bad JWT payload';

export default class JWTError extends Error {

    public constructor(message: string) {
        super(message);
        this.name = this.constructor.name;
    }

}
